<?php
/**
 * An extension to {@link HasManyPickerField} to allow managing many_many
 * relationships.
 */
class ManyManyPickerField extends HasManyPickerField {

	public function __construct($parent, $name, $title=null, $options=null) {
		if (isset($options['SortColumn'])) {
			$this->SortColumn = $options['SortColumn'];
			$options['Sortable'] = true;
		}

		parent::__construct($parent, $name, $title, $options);

		list($parentClass, $componentClass, $parentField, $componentField, $table) = $parent->many_many($this->name);
		$this->joinTable = $table;
		$this->otherClass = ( $parent->class == $parentClass || ClassInfo::is_subclass_of($parent->class, $parentClass)) ? $componentClass : $parentClass;
	}


	public function Sortable() { 
		return $this->getOption('Sortable'); 
	}

	public function Items() {
		$accessor = $this->name;
		if ($this->SortColumn) return $this->parent->getManyManyComponents($accessor, '', "\"{$this->joinTable}\".\"{$this->SortColumn}\"");
		return $this->parent->$accessor();
	}

}
