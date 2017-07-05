<?php

namespace CCL\Content\Element\Basic;

use CCL\Content\Element\Basic\Description\Description;
use CCL\Content\Element\Basic\Description\Term;

/**
 * Represents a description list element.
 *
 * @example // The following code snippet creates a description list.
 * $dl = new DescriptionList('mydl');
 * $dl->setTerm(new Term('t'))->setContent('My Term');
 * $dl->setDescription(new Description('d'))->setContent('My Description');
 */
class DescriptionList extends Container
{
	/**
	 * Defines the Term of the list.
	 * The given Term is returned for chaining calls.
	 *
	 * @param Term $term
	 *
	 * @return Term
	 */
	public function setTerm(Term $term)
	{
		return $this->addChild($term);
	}

	/**
	 * Defines the Description of the list.
	 * The given Description is returned for chaining calls.
	 *
	 * @param Description $description
	 *
	 * @return Description
	 */
	public function setDescription(Description $description)
	{
		return $this->addChild($description);
	}
}
