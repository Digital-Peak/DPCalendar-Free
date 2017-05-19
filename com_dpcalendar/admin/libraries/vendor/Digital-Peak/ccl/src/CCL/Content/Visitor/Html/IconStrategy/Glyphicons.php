<?php

namespace CCL\Content\Visitor\Html\IconStrategy;

use CCL\Content\Element\Component\Icon;
use CCL\Content\Visitor\AbstractElementVisitor;

/**
 * The Glyphicons icon strategy.
 */
class Glyphicons extends AbstractElementVisitor
{

	/**
	 * {@inheritdoc}
	 *
	 * @see \CCL\Content\Visitor\AbstractElementVisitorInterface::visitIcon()
	 */
	public function visitIcon(Icon $icon)
	{
		switch ($icon->getType()) {
			case Icon::CALENDAR:
				$icon->addClass('glyphicon glyphicon-calendar', true);
				break;
			case Icon::CANCEL:
				$icon->addClass('glyphicon glyphicon-remove-sign', true);
				break;
			case Icon::DELETE:
				$icon->addClass('glyphicon glyphicon-trash', true);
				break;
			case Icon::DOWN:
				$icon->addClass('glyphicon glyphicon-arrow-down', true);
				break;
			case Icon::DOWNLOAD:
				$icon->addClass('glyphicon glyphicon-download', true);
				break;
			case Icon::EDIT:
				$icon->addClass('glyphicon glyphicon-edit', true);
				break;
			case Icon::FILE:
				$icon->addClass('glyphicon glyphicon-file', true);
				break;
			case Icon::INFO:
				$icon->addClass('glyphicon glyphicon-info-sign', true);
				break;
			case Icon::MAIL:
				$icon->addClass('glyphicon glyphicon-envelope', true);
				break;
			case Icon::PLUS:
				$icon->addClass('glyphicon glyphicon-plus-sign', true);
				break;
			case Icon::LOCATION:
				$icon->addClass('glyphicon glyphicon-map-marker', true);
				break;
			case Icon::LOCK:
				$icon->addClass('glyphicon glyphicon-lock', true);
				break;
			case Icon::OK:
				$icon->addClass('glyphicon glyphicon-ok-sign', true);
				break;
			case Icon::PRINTING:
				$icon->addClass('glyphicon glyphicon-print', true);
				break;
			case Icon::SEARCH:
				$icon->addClass('glyphicon glyphicon-search', true);
				break;
			case Icon::SIGNUP:
				$icon->addClass('glyphicon glyphicon-log-in', true);
				break;
			case Icon::UP:
				$icon->addClass('glyphicon glyphicon-arrow-up', true);
				break;
			case Icon::USERS:
				$icon->addClass('glyphicon glyphicon-user', true);
				break;
		}
	}
}
