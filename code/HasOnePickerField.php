<?php
/**
 * An extension to {@link HasManyPickerField} to allow managing has_one
 * relationships.
 */
class HasOnePickerField extends HasManyPickerField {

	public function __construct($parent, $name, $title=null, $options=null) {
		parent::__construct($parent, $name, $title, $options);

		$this->otherClass = $parent->has_one($this->name);
	}


	public function Add($data, $item) {
		$accessorfield = $this->name . 'ID';
		$this->parent->$accessorfield = $item->ID;
		$this->parent->write();

		return Director::is_ajax() ? $this->FieldHolder() : Director::redirectBack();
	}

	public function Remove($data, $item) {
		$accessorfield = $this->name . 'ID';
		$this->parent->$accessorfield = null;
		$this->parent->write();

		return Director::is_ajax() ? $this->FieldHolder() : Director::redirectBack();
	}

	public function Items() {
		$accessor = $this->name;
		$dos = new DataObjectSet();

		// This is really nasty: there are two instances of the same db record. During HasOnePickerField::Remove()
		// we release the has_one relation for one instance and then update the field from the other which still
		// holds the hos_one set. This happens because only DataObject::get_one() calls get cached but not instances
		// retrieved with DataObject::get();
		$this->parent = DataObject::get($this->parent->ClassName, "\"ID\" = " . $this->parent->ID)->first();

		if($this->parent->$accessor() && $this->parent->$accessor()->ID) $dos->push($this->parent->$accessor());
		return $dos;
	}

	public function saveInto(DataObject $record) {
		// nothing to do here
	}

}
