<?php

namespace Yoast\WP\SEO\Dependency_Injection;

use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Yoast\WP\Lib\Migrations\Migration;
use Yoast\WP\SEO\Commands\Command_Interface;
use Yoast\WP\SEO\Initializers\Initializer_Interface;
use Yoast\WP\SEO\Integrations\Integration_Interface;
use Yoast\WP\SEO\Loader;
use Yoast\WP\SEO\Routes\Route_Interface;

/**
 * A pass is a step in the compilation process of the container.
 *
 * This step will automatically ensure all classes implementing the Integration interface
 * are registered with the Loader class.
 */
class Loader_Pass implements CompilerPassInterface {

	/**
	 * Checks all definitions to ensure all classes implementing the Integration interface
	 * are registered with the Loader class.
	 *
	 * @param ContainerBuilder $container The container.
	 */
	public function process( ContainerBuilder $container ) {
		if ( ! $container->hasDefinition( Loader::class ) ) {
			return;
		}

		$loader_definition = $container->getDefinition( Loader::class );
		$definitions       = $container->getDefinitions();

		foreach ( $definitions as $definition ) {
			if ( $definition->isDeprecated() ) {
				continue;
			}

			$this->process_definition( $definition, $loader_definition );
		}
	}

	/**
	 * Processes a definition in the container.
	 *
	 * @param Definition $definition        The definition to process.
	 * @param Definition $loader_definition The loader definition.
	 */
	private function process_definition( Definition $definition, Definition $loader_definition ) {
		$class = $definition->getClass();

		try {
			$reflect = new ReflectionClass( $class );
			$path    = $reflect->getFileName();
			if ( \strpos( $path, 'src' . \DIRECTORY_SEPARATOR . 'analytics' ) && ! \strpos( $path, 'missing-indexables-collector' ) && ! \strpos( $path, 'to-be-cleaned-indexables-collector' )
			) {
				$definition->setPublic( false );
			}
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Catch all for non-existing classes.
		}

		if ( \is_subclass_of( $class, Initializer_Interface::class ) ) {
			$loader_definition->addMethodCall( 'register_initializer', [ $class ] );
		}

		if ( \is_subclass_of( $class, Integration_Interface::class ) ) {
			$loader_definition->addMethodCall( 'register_integration', [ $class ] );
			$definition->setPublic( true );
		}

		if ( \is_subclass_of( $class, Route_Interface::class ) ) {
			$loader_definition->addMethodCall( 'register_route', [ $class ] );
		}

		if ( \is_subclass_of( $class, Command_Interface::class ) ) {
			$loader_definition->addMethodCall( 'register_command', [ $class ] );
		}

		if ( \is_subclass_of( $class, Migration::class ) ) {
			$reflect = new ReflectionClass( $class );
			$path    = $reflect->getFileName();
			$file    = \basename( $path, '.php' );
			$version = \explode( '_', $file )[0];
			$plugin  = $class::$plugin;
			$loader_definition->addMethodCall( 'register_migration', [ $plugin, $version, $class ] );
		}
	}
}
