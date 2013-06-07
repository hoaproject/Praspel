<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2013, Ivan Enderlin. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Hoa nor the names of its contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace {

from('Hoa')

/**
 * \Hoa\Praspel\Exception\Compiler
 */
-> import('Praspel.Exception.Compiler')

/**
 * \Hoa\Visitor\Visit
 */
-> import('Visitor.Visit');

}

namespace Hoa\Praspel\Visitor {

/**
 * Class \Hoa\Praspel\Visitor\Praspel.
 *
 * Compile the model to Praspel code.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2013 Ivan Enderlin.
 * @license    New BSD License
 */

class Praspel implements \Hoa\Visitor\Visit {

    /**
     * Visit an element.
     *
     * @access  public
     * @param   \Hoa\Visitor\Element  $element    Element to visit.
     * @param   mixed                 &$handle    Handle (reference).
     * @param   mixed                 $eldnah     Handle (not reference).
     * @return  mixed
     */
    public function visit ( \Hoa\Visitor\Element $element,
                            &$handle = null, $eldnah = null ) {

        $out = null;

        if($element instanceof \Hoa\Praspel\Model\Specification) {

            $clauses  = array(
                'is',
                'invariant',
                'requires',
                'ensures',
                'behavior',
                'throwable',
                'description'
            );

            $oout = array();

            foreach($clauses as $clause)
                if(true === $element->clauseExists($clause))
                    $oout[] = $element->getClause($clause)->accept(
                        $this,
                        $handle,
                        $eldnah
                    );

            $out = implode("\n", $oout);
        }
        elseif($element instanceof \Hoa\Praspel\Model\Is) {

            $out = '@is ' . $element->getPropertyName() . ';';
        }
        elseif($element instanceof \Hoa\Praspel\Model\Declaration) {

            $clause = $element->getName();
            $out    = '@' . $clause;
            $oout   = array();

            foreach($element as $name => $var)
                $oout[] = ' ' . $var->accept($this, $handle, $eldnah);

            foreach($element->getPredicates() as $predicate)
                $oout[] = ' \pred(\'' . $predicate . '\')';

            $out .= implode(' and', $oout) . ';';
        }
        elseif($element instanceof \Hoa\Praspel\Model\Variable) {

            $name = $element->getName();

            if(true === $element->isLocal())
                $out = 'let ';

            $out .= $name;

            if(null === $alias = $element->getAlias())
                $out .= ': ' . $element->getDomains()->toPraspel();
            else
                $out .= ' domainof ' . $alias;

            $constraints = $element->getConstraints();

            if(isset($constraints['is']))
                $out .= ' and ' . $name . ' is ' .
                        implode(', ', $constraints['is']);

            if(isset($constraints['contains']))
                foreach($constraints['contains'] as $contains)
                    $out .= ' and ' . $name . ' contains ' .
                            $contains->toPraspel();

            if(isset($constraints['key']))
                foreach($constraints['key'] as $pairs)
                    $out .= ' and ' . $name . '[' . $pairs[0]->toPraspel() .
                            ']: ' . $pairs[1]->toPraspel();
        }
        elseif($element instanceof \Hoa\Praspel\Model\Throwable) {

            $oout = array();

            foreach($element->getExceptions() as $identifier => $class)
                $oout[] = ' ' . $class . ' ' . $identifier;

            $out = '@throwable' . implode(' or', $oout) . ';';
        }
        elseif($element instanceof \Hoa\Praspel\Model\Behavior) {

            $out     = '@behavior ' . $element->getIdentifier() . ' {' . "\n";
            $clauses = array(
                'invariant',
                'requires',
                'ensures',
                'behavior',
                'throwable'
            );

            $oout = array();

            foreach($clauses as $clause)
                if(true === $element->clauseExists($clause))
                    $oout[] = '    ' . str_replace(
                        "\n",
                        "\n" . '    ',
                        $element->getClause($clause)->accept(
                            $this,
                            $handle,
                            $eldnah
                        )
                    );

            $out .= implode("\n", $oout) . "\n" . '}';

        }
        elseif($element instanceof \Hoa\Praspel\Model\Description) {

            $oout = array();

            foreach($element as $example)
                $oout[] = '@description \'' .
                          preg_replace('#(?<!\\\)\'#', '\\\'', $example) .
                          '\';';

            $out = implode("\n", $oout);
        }
        elseif($element instanceof \Hoa\Praspel\Model\Collection)
            foreach($element as $el)
                $out .= $el->accept($this, $handle, $eldnah);
        else
            throw new \Hoa\Praspel\Exception\Compiler(
                '%s is not yet implemented.', 0, get_class($element));

        return $out;
    }
}

}
