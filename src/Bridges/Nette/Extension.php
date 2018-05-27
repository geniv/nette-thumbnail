<?php declare(strict_types=1);

namespace Thumbnail\Bridges\Nette;

use Nette\DI\CompilerExtension;
use Nette\PhpGenerator\ClassType;
use Nette\Utils\Image;
use Thumbnail\MacroSrc;
use Thumbnail\Thumbnail;


/**
 * Class Extension
 *
 * @author  geniv
 * @package Thumbnail\Bridges\Nette
 */
class Extension extends CompilerExtension
{
    /** @var array default values */
    private $defaults = [
        'dir'         => null,
        'thumbPath'   => null,
        'noImage'     => null,
        'waitImage'   => null,
        'lazyLoad'    => false,
        'defaultFlag' => Image::SHRINK_ONLY,
        'template'    => [],
    ];


    /**
     * Before Compile.
     */
    public function beforeCompile()
    {
        $builder = $this->getContainerBuilder();
        $config = $this->validateConfig($this->defaults);

        $builder->addDefinition($this->prefix('default'))
            ->setFactory(Thumbnail::class, [$config]);

        // load macro to latte
        $latteFactory = $builder->getDefinition('latte.latteFactory');
        $latteFactory->addSetup(MacroSrc::class . '::install(?->getCompiler())', ['@self']);
    }


    /**
     * After compile.
     *
     * @param ClassType $class
     */
    public function afterCompile(ClassType $class)
    {
        $initialize = $class->getMethod('initialize');
        $initialize->addBody('$this->getService(?);', [$this->prefix('default')]);  // global call for initialization
    }
}
