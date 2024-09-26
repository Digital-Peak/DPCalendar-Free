<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2024 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die;

use DigitalPeak\Component\DPCalendar\Administrator\Extension\DPCalendarComponent;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\CategoryFactory;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {
	public function register(Container $container): void
	{
		$container->registerServiceProvider(new MVCFactory('\\DigitalPeak\\Component\\DPCalendar'));
		$container->registerServiceProvider(new ComponentDispatcherFactory('\\DigitalPeak\\Component\\DPCalendar'));
		$container->registerServiceProvider(new RouterFactory('\\DigitalPeak\\Component\\DPCalendar'));
		$container->registerServiceProvider(new CategoryFactory('\\DigitalPeak\\Component\\DPCalendar'));

		$container->set(
			ComponentInterface::class,
			static function (Container $container): DPCalendarComponent {
				$component = new DPCalendarComponent($container->get(ComponentDispatcherFactoryInterface::class));
				$component->setMVCFactory($container->get(MVCFactoryInterface::class));
				$component->setRouterFactory($container->get(RouterFactoryInterface::class));
				$component->setCategoryFactory($container->get(CategoryFactoryInterface::class));
				$component->setDatabase($container->get(DatabaseInterface::class));
				return $component;
			}
		);
	}
};
