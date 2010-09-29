<?php
/**
 * An extension to {@link HasManyPickerField} to allow managing many_many
 * relationships.
 */
class ManyManyPickerField extends HasManyPickerField {
	
	/**
	 * @var int
	 */
	protected $searchLimit = null; 

	public function __construct($parent, $name, $title=null, $options=null) {
		if (isset($options['SortColumn'])) {
			$this->SortColumn = $options['SortColumn'];
			$options['Sortable'] = true;
		}
		
		if (isset($options['SearchLimit'])) {
			$this->searchLimit = $options['SearchLimit'];
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
		if ($this->SortColumn) {
			return $this->parent->getManyManyComponents($accessor, '', "\"{$this->joinTable}\".\"{$this->SortColumn}\"");
		}
		else {
			return $this->parent->$accessor();
		}
	}

	public function ResultsForm($search) {
		if ($search instanceof SS_HTTPRequest) {
			$search = $search->getVars();
		}

		$field = new ManyManyPickerField_SearchField($this);
		$field->setSearchCriteria($search);
		if($this->searchLimit) $field->setLimit($this->searchLimit); 

		return new Form(
			$this, 'ResultsForm', new FieldSet($field), new FieldSet()
		);
	}

}

class ManyManyPickerField_SearchField extends HasManyPickerField_SearchField {

	public function ItemClass() {
		return 'ManyManyPickerField_Item';
	}

}

class ManyManyPickerField_Item extends ItemSetField_ListItem {

	public function Choose($data) {
		// If we need to get additional data to populate the extra relationship
		// fields, then an extra fields form.
		if (!$this->parent->parent->getOption('ExtraFields')) {
			return $this->parent->Choose($data, $this->item);
		}

		return $this->AddForm()->forAjaxTemplate();
	}

	/**
	 * @return Form
	 */
	public function AddForm() {
		$method = $this->parent->parent->getOption('ExtraFields');
		$fields = $this->parent->parent->parent->$method();

		return new Form($this, 'AddForm', $fields, new FieldSet(
			new FormAction('doAdd', _t('ItemSetField.ADD', 'Add'))
		));
	}

	public function doAdd($data, $form) {
		$field = $this->parent->parent;
		$name  = $field->Name();

		$set   = $field->parent->$name();
		$extra = $field->parent->many_many_extraFields($name);

		$data  = array_intersect_key($form->getData(), $extra);
		$set->add($this->item, $data);

		return $this->parent->parent->FieldHolder();
	}

}
