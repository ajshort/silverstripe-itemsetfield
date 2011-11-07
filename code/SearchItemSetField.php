<?php

class SearchItemSetField extends ItemSetField {

	function __construct($class, $name, $title=null, $options=null) {
		parent::__construct($name, $title, $options);
		$this->searchClass = $class;
	}

	function setSearchCriteria($searchCriteria) {
		$this->searchCriteria = $searchCriteria;
	}

	public function getItemsQuery() {
		if (!$this->searchCriteria) return;

		$class   = singleton($this->searchClass);
		$context = $class->getDefaultSearchContext();

		return $context->getQuery($this->searchCriteria);
	}

	public function getItemById($id) {
		return DataObject::get_by_id($this->searchClass, $id);
	}

}
