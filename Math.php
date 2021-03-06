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

namespace RL\MathBundle;

use RL\PhpMathPublisher\PhpMathPublisher;
use RL\MathBundle\Model\Latex;

/**
 * RL\MathBundle\Math
 *
 * @author Peter Vasilevsky <tuxoiduser@gmail.com> a.k.a. Tux-oid
 * @license BSDL
 */
class Math
{
    /**
     * @var string
     */
    protected $formula;

    /**
     * @var \RL\PhpMathPublisher\PhpMathPublisher
     */
    protected $mathPublisher;

    /**
     * @var bool
     */
    protected $useLatex;

    /**
     * @var \RL\MathBundle\Model\Latex
     */
    protected $latex;

    /**
     * Constructor
     *
     * @param string $path
     * @param bool $useLatex
     */
    public function __construct($path, $useLatex, Latex $latex)
    {
        $this->mathPublisher = new PhpMathPublisher($path);
        $this->useLatex = $useLatex;
        $this->latex = $latex;
    }

    /**
     * @param string|null $formula
     */
    public function render($formula = null)
    {
        if (null === $formula) {
            $formula = $this->formula;
        }
        if ($this->useLatex) {
            if ($this->latex->isAvailable()) {
                return $this->latex->makeImage($formula);
            }
        }
        return $this->mathPublisher->mathFilter('<m>' . trim($formula) . '</m>');
    }

    /**
     * @param string $formula
     */
    public function setFormula($formula)
    {
        $this->formula = $formula;
    }

    /**
     * @return string
     */
    public function getFormula()
    {
        return $this->formula;
    }

    /**
     * @param \RL\phpMathPublisher\PhpMathPublisher $mathPublisher
     */
    public function setMathPublisher(PhpMathPublisher $mathPublisher)
    {
        $this->mathPublisher = $mathPublisher;
    }

    /**
     * @return \RL\phpMathPublisher\PhpMathPublisher
     */
    public function getMathPublisher()
    {
        return $this->mathPublisher;
    }

}
