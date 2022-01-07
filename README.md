# PMC Unit Test Framework

---

## Introduction

This plugin was originally written for internal use at Penske Media to provide common utilities and data mocking for unit tests in a WordPress environment. Our hope is that other teams find this plugin as useful as we do when writing unit tests in WordPress.

## Dependencies

## Documentation

- [Data Mocking](docs/data-mocking.md)

## Installation

	composer require pmc/unit-test

## Usage

Create the bootstrap.php file:

	<?php
	namespace ExampleTestNameSpace; 
	
	use PMC\Unit_Test\Bootstrap;
	use PMC\Unit_Test\Autoloader;
	
	// Load the composer autoloader.php file
	require_once __DIR__ . '/vendor/autoload.php';
	
	// Register the current test project namespace as needed 
	Autoloader::register( __NAMESPACE__, __DIR__ );
	
	// Instantiate the unit test bootstrap
	$bootstrap = Bootstrap::get_instance();

	// Activate any installed plugins as needed
	$bootstrap->activate_plugins( [ 'amp' ] );

	// Some custom code to load plugin with VIP plugin loader
	tests_add_filter( 'plugins_loaded', function() {
		wpcom_vip_load_plugin( 'some-plugin' );
	} );
	
	// Start the unit test process
	$bootstrap->start();
	// EOF

Add project phpunit.xml file:

	<phpunit
	bootstrap="tests/bootstrap.php"
	backupGlobals="false"
	colors="true"
	convertErrorsToExceptions="true"
	convertNoticesToExceptions="true"
	convertWarningsToExceptions="true"
	>
		<testsuites>
			<testsuite name="test">
				<directory prefix="test-" suffix=".php">./tests/</directory>
			</testsuite>
		</testsuites>
		<filter>
			<whitelist processUncoveredFilesFromWhitelist="true">
				<directory suffix=".php">./src</directory>
			</whitelist>
		</filter>
		<logging>
			<log type="coverage-text" target="php://stdout" showUncoveredFiles="true" />
		</logging>
	</phpunit>


Extends the PMC unit test base class:

	<?php
	namespace ExampleTestNameSpace;
	
	use PMC\Unit_Test\Base;
	
	class Test_Example extends Base {
		public function test_example() {
			$post = $this->mock->post( [
					'post_title' => 'test example',
				] )->get();
			$this->assertEquals( 'test example', $post->post_title );
		}
	}
