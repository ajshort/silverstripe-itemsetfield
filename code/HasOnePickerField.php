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
		if ($this->parent->ID) {
			$this->parent->write();
		}

		return Director::is_ajax() ? $this->FieldHolder() : Director::redirectBack();
	}

	public function Remove($data, $item) {
		$accessorfield = $this->name . 'ID';
		$this->parent->$accessorfield = null;
		if ($this->parent->ID) {
			$this->parent->write();
		}
		$this->parent->flushCache();

		return Director::is_ajax() ? $this->FieldHolder() : Director::redirectBack();
	}

	public function Delete($data, $item) {
		if (!$item->canDelete()) {
			$this->httpError(403);
		}

		$this->parent->{$this->name . 'ID'} = null;
		if ($this->parent->ID) {
			$this->parent->write();
		}
		$item->delete();

		return Director::is_ajax() ? $this->FieldHolder() : Director::redirectBack();
	}

	public function Items() {
		if ($item = $this->parent->{$this->name . 'ID'}) {
			return new DataObjectSet(array(
				$this->parent->{$this->name}()
			));
		}
	}

	public function saveInto(DataObject $record) {
		if($this->value && is_array($this->value)){
			$accessorfield = $this->name . 'ID';
			$record->$accessorfield = $this->value[0];
		}
	}

}
