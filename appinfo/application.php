<?php

namespace OCA\ForceDelete\AppInfo;

use OCP\AppFramework\App;
use OCP\IContainer;
use OCA\ForceDelete\Delete;
use OCA\ForceDelete\Controller\ForceDeleteController;

class Application extends App {
	public function __construct (array $urlParams = array()) {
		parent::__construct('forcedelete', $urlParams);
		$container = $this->getContainer();

        $container->registerService('ForceDeleteL10N', function(IContainer $c) {
			return $c->query('ServerContainer')->getL10N('forcedelete');
		});

        $container->registerService('DeleteAction', function(IContainer $c) {
			/** @var \OC\Server $server */
			$server = $c->query('ServerContainer');
			return new Delete (
				$server->getUserSession()
			);
		});

		$container->registerService('ForceDeleteController', function(IContainer $c) {
			/** @var \OC\Server $server */
			$server = $c->query('ServerContainer');

			return new ForceDeleteController (
				$c->query('AppName'),
				$server->getRequest(),
				$c->query('DeleteAction')
			);
		});

    } 
}

?>
