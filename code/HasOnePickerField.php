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

	public function UpdateForm($otherObject = null) {
		if($otherObject instanceof SS_HTTPRequest) $otherObject = DataObject::get_by_id($this->otherClass, (int)$otherObject->param('ItemID'));
		if(!$otherObject) $otherObject = singleton($this->otherClass);

		$fields = $otherObject->hasMethod('getCMSFields_forPopup') ? $otherObject->getCMSFields_forPopup() : $otherObject->scaffoldFormFields(array('ajaxSafe' => true));

		$fields->push(new HiddenField('ItemID', 'ItemID' , $otherObject->ID));

		$form = new Form($this, 'UpdateForm',
			$fields,
			new FieldSet(
				new FormAction('Update', _t('MemberTableField.EDIT', 'Save')),
				new ResetFormAction('ClearEdit', _t('ModelAdmin.CLEAR_EDIT','Clear Form'))
			)
		);
		$form->setFormMethod('get');

		return $form;
	}

	function Update($data,$form) {
		$otherObject = DataObject::get_by_id($this->otherClass, (int)$data['ItemID']);
		if(!$otherObject) $otherObject = new $this->otherClass();
		$form->saveInto($otherObject);
		$otherObject->write();

		$assoc = $this->name . 'ID';
		if($this->options['ManagePicked'] && $this->options['DeleteRemoved'] && $this->parent->$assoc != $otherObject->ID) {
			$old = DataObject::get_by_id($this->otherClass, (int)$this->parent->$assoc);
			if($old) $old->delete();
		}
		$this->parent->$assoc = $otherObject->ID;
		$this->parent->write();

		return Director::is_ajax() ? $this->FieldHolder() : Director::redirectBack();
	}

	public function Add($data, $item) {
		$accessorfield = $this->name . 'ID';
		
		if($this->options['ManagePicked'] && $this->options['DeleteRemoved'] && $this->parent->$accessorfield != $item->ID) {
			$old = DataObject::get_by_id($this->otherClass, (int)$this->parent->$accessorfield);
			if($old) $old->delete();
		}

		$this->parent->$accessorfield = $item->ID;
		$this->parent->write();

		return Director::is_ajax() ? $this->FieldHolder() : Director::redirectBack();
	}

	public function Remove($data, $item) {
		$accessorfield = $this->name . 'ID';
		$this->parent->$accessorfield = null;
		$this->parent->write();
		$this->parent->flushCache();

		if($this->options['ManagePicked'] && $this->options['DeleteRemoved']) $item->delete();

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
		// nothing to do here
	}

}
