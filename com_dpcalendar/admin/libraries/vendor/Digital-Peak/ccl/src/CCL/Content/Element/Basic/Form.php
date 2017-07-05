<?php

namespace CCL\Content\Element\Basic;

/**
 * Represents a form element.
 *
 * @example // The following code snippet creates a form.
 * $f = new Form('myform', 'url/to/form.html', 'myForm', 'GET');
 */
class Form extends Container
{
	/**
	 * Constructor which sets the classes and attributes of the element.
	 * The action attribute must be a url and the name given to successfully create a form element.
	 *
	 * The id, action and name parameter must be set, otherwise an InvalidArgumentException is thrown.
	 *
	 * @param string $id         The id of the element, must be not empty
	 * @param string $action     The action if the form
	 * @param string $name       The name of the form
	 * @param string $method     The method of the form, can be GET or POST
	 * @param array  $classes    The classes of the element
	 * @param array  $attributes Additional attributes for the element
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct($id, $action, $name, $method = 'POST', array $classes = [], array $attributes = [])
	{
		if (!$action) {
			throw new \InvalidArgumentException('Action can not be empty!');
		}

		if (!$name) {
			throw new \InvalidArgumentException('Name can not be empty!');
		}

		$attributes['action'] = $action;
		$attributes['name']   = $name;
		$attributes['method'] = $method;

		parent::__construct($id, $classes, $attributes);
	}
}
