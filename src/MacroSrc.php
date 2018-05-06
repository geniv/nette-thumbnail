<?php declare(strict_types=1);

namespace Thumbnail;

use Latte;
use Latte\CompileException;
use Latte\MacroNode;
use Latte\Macros\MacroSet;
use Latte\PhpWriter;


/**
 * Class MacroSrc
 *
 * @author  geniv
 * @package Thumbnail
 */
class MacroSrc extends MacroSet
{

    /**
     * Install.
     *
     * @param Latte\Compiler $compiler
     */
    public static function install(Latte\Compiler $compiler)
    {
        $me = new static($compiler);
        $me->addMacro('src', [$me, 'macroSrc'], null, [$me, 'macroSrc']);
    }


    /**
     * Macro src.
     *
     * @param MacroNode $node
     * @param PhpWriter $writer
     * @return string
     * @throws CompileException
     */
    public function macroSrc(MacroNode $node, PhpWriter $writer)
    {
        if ($node->modifiers) {
            throw new CompileException('Modifiers are not allowed in ' . $node->getNotation());
        }
        return $writer->write('echo \' src="\'.Thumbnail\\Thumbnail::getSrcPath(%node.word, %node.args).\'"\'');
    }
}
