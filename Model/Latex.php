<?php
/**
 * Copyright (c) 2008 - 2012, Peter Vasilevsky
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the RL nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL PETER VASILEVSKY BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

namespace RL\MathBundle\Model;

use Symfony\Component\HttpKernel\Log\LoggerInterface;

/**
 * RL\MathBundle\Model\Latex
 *
 * This class used to create images from LaTeX markup.
 *
 * There are two available methods to convert tex to png:
 * - latex -> dvips -> imagemagick. This method crates images with best quality.
 * - latex -> dvipng. This method creates images with slightly worse quality.
 *
 * Usage:
 * $math = new Latex();
 * if ($math->isAvailable())
 *    $img = $math->makeImage($text);
 *
 * @author Vladimir Gorbunov <truedaemon@gmail.com>  a.k.a. SystemV
 * @author Peter Vasilevsky <tuxoiduser@gmail.com> a.k.a. Tux-oid
 * @license BSDL
 */
/**
 *
 */
class Latex
{

    /**
     * @var null|string
     */
    private $latexPath = null;

    /**
     * @var null|string
     */
    private $convertPath = null;

    /**
     * @var null|string
     */
    private $dviPsPath = null;

    /**
     * @var null|string
     */
    private $dviPngPath = null;

    /**
     * @var null|string
     */
    private $tmpDir = null;

    /**
     * @var null|string
     */
    private $imgDir = null;

    /**
     * @var null|string
     */
    private $imgUrl = null;

    /**
     * @var \Symfony\Component\HttpKernel\Log\LoggerInterface
     */
    protected $logger;

    /**
     * From https://github.com/marklundeberg/dokuwiki-plugin-latex/blob/master/class.latexrender.php
     *
     * @var array
     */
    public $tagsBlacklist = array(
        "include",
        "def",
        "command",
        "loop",
        "repeat",
        "open",
        "toks",
        "output",
        "input",
        "catcode",
        "name",
        "^^",
        "\\every",
        "\\errhelp",
        "\\errorstopmode",
        "\\scrollmode",
        "\\nonstopmode",
        "\\batchmode",
        "\\read",
        "\\write",
        "csname",
        "\\newhelp",
        "\\uppercase",
        "\\lowercase",
        "\\relax",
        "\\aftergroup",
        "\\afterassignment",
        "\\expandafter",
        "\\noexpand",
        "\\special"
    );

    /**
     * @var int
     */
    public $maxLength = 2048;

    /**
     * @var string
     */
    private $tpl = <<<EOT
	\\documentclass{report}
	\\pagestyle{empty}
        \\usepackage[T2A]{fontenc}
        \\usepackage[utf8]{inputenc}
        \\usepackage[russian]{babel}
	\\usepackage{amsmath}
	\\usepackage{amsfonts}
	\\usepackage{amssymb}
	\\usepackage{color}
	\\begin{document}
	{{ TEXT }}
	\\end{document}
EOT;

    /**
     * Constructor
     *
     * @param string|null $imgDir
     * @param string|null $imgUrl
     * @param string|null $tmpDir
     * @param string|null $logger
     */
    public function __construct($imgDir, $imgUrl, $tmpDir, LoggerInterface $logger)
    {
        $this->imgDir = $imgDir;
        $this->imgUrl = $imgUrl;
        $this->tmpDir = $tmpDir;
        $this->logger = $logger;
        /* Get path for all binaries */
        $this->latexPath = $this->getPath('latex');
        $this->convertPath = $this->getPath('convert');
        $this->dviPsPath = $this->getPath('dvips');
        $this->dviPngPath = $this->getPath('dvipng');
        $this->dirname = null;
        $this->logger->info(gmdate("Y-m-d H:i:s") . "\n");
    }

    /**
     * Get full path to command
     *
     * @param string $cmd
     * @return string
     */
    private function getPath($cmd)
    {
        $line = exec('command -v ' . $cmd, $output);
        $this->logger->info(implode("\n", $output));

        return $line;
    }

    /**
     * Check if required commands are available in this system
     *
     * @return bool
     */
    public function isAvailable()
    {
        if (empty($this->latexPath)) {
            return false;
        }
        if ((empty($this->dviPsPath) && empty($this->convertPath)) &&
            (empty($this->dviPngPath))
        ) {
            return false;
        }

        return true;
    }

    /**
     * Exec command
     *
     * @param string $cmd
     * @return array
     */
    private function exec($cmd)
    {
        exec('TEXMFVAR=' . $this->tmpDir . 'texmf/ ' . $cmd, $output, $code);
        $this->logger->info(implode("\n", $output));

        return array($code, $output);
    }

    /**
     * Remove file or directory
     *
     * @param string $path
     */
    private function rm($path)
    {
        exec('/bin/rm -r ' . $path);
    }

    /**
     * Remove blacklisted tags. Is this safe?
     *
     * @param string $text
     * @return string
     */
    private function removeTags($text)
    {
        for ($i = 0; $i < count($this->tagsBlacklist); $i++) {
            $text = str_replace($this->tagsBlacklist[$i], '', $text);
        }

        return $text;
    }

    /**
     * Clean dir
     */
    public function clean()
    {
        $this->rm($this->dirname);
    }

    /**
     * Create image from LaTeX markup
     *
     * @param string $text
     * @return string
     * @throws \Exception
     */
    private function process($text)
    {
        $text = trim($text);
        if (strlen($text) > $this->maxLength) {
            throw new \Exception('Message too long (limit: ' . $this->maxLength . ' chars)');
        }
        $name = sha1($text . strlen($text));
        $text = $this->removeTags($text);
        $doc = str_replace('{{ TEXT }}', $text, $this->tpl);
        /* Set names and paths */
        $this->dirname = $this->tmpDir . $name . '/';
        $fileNameBase = $this->dirname . $name; /* Base file name, absolute path */
        $resultName = 'math_' . $name . '.png'; /* Result file name */
        $result = $this->imgDir . $resultName; /* Result file path */
        /* Create temporary directory */
        $this->clean();
        mkdir($this->dirname);
        $f = fopen($fileNameBase . '.tex', 'w');
        fwrite($f, $doc);
        /* Run latex */
        list($err, $out) = $this->exec(
            $this->latexPath . ' -interaction=nonstopmode -output-directory=' . $this->dirname . ' ' . $fileNameBase . '.tex'
        );
        if ($err !== 0) {
            $out = str_replace($_SERVER['DOCUMENT_ROOT'], '', implode("\n", array_slice($out, 1)));
            throw new \Exception('latex can\'t process this text <div class="quote"><pre>' . $out . '</pre></div>');
        }
        /* dvips->imagemagick */
        if (!empty($this->dviPsPath) && !empty($this->convertPath)) {
            list($err, $out) = $this->exec($this->dviPsPath . ' -E -o ' . $fileNameBase . '.ps ' . $fileNameBase . '.dvi');
            if ($err !== 0) {
                throw new \Exception('dvips error');
            }
            list($err, $out) = $this->exec(
                $this->convertPath . ' -density 150 -background "#fffffe" -flatten ' .
                    $fileNameBase . '.ps ' . $result
            );
            if ($err !== 0) {
                throw new \Exception('convert error');
            }
        } /* dvipng */
        elseif (!empty($this->dviPngPath)) {
            list($err, $out) = $this->exec(
                $this->dviPngPath . ' -D 150 -T tight ' .
                    $fileNameBase . '.dvi -o ' . $result
            );
            if ($err !== 0) {
                throw new \Exception('dvipng error');
            }
        }
        /* Clean and exit */
        $this->clean();

        return $this->imgUrl . $resultName;
    }

    /**
     * Public wrapper for process method
     *
     * @param string $text
     * @return string
     */
    public function makeImage($text)
    {
        try {
            $img = $this->process($text);

            return '<img src="' . $img . '" alt="" />';
        } catch (\Exception $e) {
            $this->clean();

            return '<p>Error: ' . $e->getMessage() . '</p>';
        }
    }
}
