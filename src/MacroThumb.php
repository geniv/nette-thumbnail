<?php declare(strict_types=1);

namespace Thumbnail;

use Latte;
use Latte\CompileException;
use Latte\Helpers;
use Latte\MacroNode;
use Latte\Macros\MacroSet;
use Latte\PhpWriter;


/**
 * Class MacroThumb
 *
 * @author  geniv
 * @package Thumbnail
 */
class MacroThumb extends MacroSet
{

    /**
     * Install.
     *
     * @param Latte\Compiler $compiler
     */
    public static function install(Latte\Compiler $compiler)
    {
        $me = new static($compiler);
        $me->addMacro('thumb', [$me, 'macroThumb']);    // {thumb }
    }


    /**
     * Macro thumb.
     *
     * @param MacroNode $node
     * @param PhpWriter $writer
     * @return string
     * @throws CompileException
     */
    public function macroThumb(MacroNode $node, PhpWriter $writer)
    {
        if ($node->modifiers) {
            // accept dataStream modifier for base64
            if (Helpers::removeFilter($node->modifiers, 'dataStream')) {
                return $writer->write('$_fi=Thumbnail\\Thumbnail::getSrcPath(%node.word, %node.args); echo Latte\\Runtime\\Filters::dataStream(file_get_contents(__DIR__."/../../../".$_fi));');
            }
            throw new CompileException('Modifiers are not allowed in ' . $node->getNotation());
        }
        return $writer->write('echo Thumbnail\\Thumbnail::getSrcPath(%node.word, %node.args)');
    }
}
