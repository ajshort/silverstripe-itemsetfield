<?php
/**
 * A picker field with searching for a has_many relationship.
 */
class HasManyPickerField extends ItemSetField {

	public static $default_options = array(
		'Sortable'           => false,
		'SortableField'      => 'Sort',
		'ExtraFilter'        => false,
		'ShowPickedInSearch' => true,
		'ManagePicked'       => false,
		'DeleteRemoved'      => false,
		'Searchable'         => true,
	);

	public static $actions = array(
		'Search' => 'Search'
	);

	protected $parent;
	protected $otherClass;
	protected $searchField;
	protected $searchFieldClass = 'ManyManyPickerField_SearchField';

	public function __construct($parent, $name, $title = null, $options = null) {
		$this->parent     = $parent;
		$this->otherClass = $parent->has_many($name);

		parent::__construct($name, $title, $options);

		if($this->options['ManagePicked']) self::$actions['Edit'] = 'New';
		if(!$this->options['Searchable']) unset(self::$actions['Search']);
	}

	/**
	 * @return HasManyPickerField_SearchField
	 */
	public function getSearchField() {
		if (!$this->searchField) {
			$this->searchField = new $this->searchFieldClass($this);
		}

		return $this->searchField;
	}

	/**
	 * @return string
	 */
	public function getOtherClass() {
		return $this->otherClass;
	}

	/**
	 * Returns the table name where the sortable field is stored.
	 *
	 * @return string
	 */
	public function getSortableTable() {
		$classes = ClassInfo::ancestry($this->getOtherClass());
		$field   = $this->getOption('SortableField');

		foreach (array_reverse($classes) as $class) {
			if (singleton($class)->hasOwnTableDatabaseField($field)) return $class;
		}

		throw new Exception("Could not find the sort field \"$field\" on {$this->getOtherClass()}");
	}

	/**
	 * Returns the field on the sortable table that corresponds to the child ID.
	 *
	 * @return string
	 */
	public function getSortableTableIdField() {
		return 'ID';
	}

	/**
	 * Returns an SQL WHERE clause that limits the results from the sortable
	 * table to either one or many item IDs.
	 *
	 * @param  int|array $ids
	 * @return string
	 */
	public function getSortableTableClauseForIds($ids) {
		if (is_array($ids)) {
			return '"ID" IN (' . implode(', ', array_map('intval', $ids)) . ')';
		} else {
			return '"ID" = ' . (int) $ids;
		}
	}

	public function getItemsQuery() {
		if ($this->getOption('Sortable')) {
			$sort = sprintf('"%s"."%s" ASC',
				$this->getSortableTable(),
				$this->getOption('SortableField'));
		} else {
			$sort = null;
		}

		return $this->parent->getComponentsQuery($this->name, null, $sort);
	}

	public function ItemActions($item) {
		$actions = parent::ItemActions($item);
		if($this->options['ManagePicked']) $actions->push(new ItemSetField_Action($this, 'Edit', 'Edit'));
		$removelable = $this->options['ManagePicked'] && $this->options['DeleteRemoved'] ? 'Delete' : 'Remove';
		$actions->push(new ItemSetField_Action($this, 'Remove', $removelable, true));
		return $actions;
	}

	public function saveInto(DataObject $record) {
		$set = $record->{$this->name}();
		$set->setByIDList($this->value);

		if (!$this->getOption('Sortable') || !count($set)) return;

		$field = $this->getOption('SortableField');
		$table = $this->getSortableTable();

		// First populate the sort values on each item that doesn't have one
		foreach ($set as $item) {
			if ($item->$field) continue;

			$max = DB::query(sprintf('SELECT MAX("%s") + 1 FROM "%s"', $field, $table));
			$max = $max->value();

			DB::query(sprintf(
				'UPDATE "%s" SET "%s" = %d WHERE %s',
				$table,
				$field,
				$max,
				$this->getSortableTableClauseForIds($item->ID)));
		}

		// Now load a list of all the possible sort values, so we don't use
		// any duplicates.
		$sorts = DB::query(sprintf(
			'SELECT "%s", "%s" FROM "%s" WHERE %s',
			$this->getSortableTableIdField(),
			$field,
			$table,
			$this->getSortableTableClauseForIds($set->map('ID', 'ID'))));

		$sortMap  = $sorts->map();
		$sortVals = array_values($sortMap);
		sort($sortVals);

		// Now loop through each update and set the sort value.
		foreach ($this->value as $k => $id) {
			$sort = $sortVals[$k];

			if ($sort != $sortMap[$id]) DB::query(sprintf(
				'UPDATE "%s" SET "%s" = %d WHERE %s',
				$table,
				$field,
				$sort,
				$this->getSortableTableClauseForIds($id)));
		}
	}

	public function SearchForm() {
		$context = singleton($this->otherClass)->getDefaultSearchContext();
		$fields  = $context->getSearchFields();

		$form = new Form($this, 'SearchForm',
			$fields,
			new FieldSet(
				new FormAction('Search', _t('MemberTableField.SEARCH', 'Search')),
				new ResetFormAction('ClearSearch', _t('ModelAdmin.CLEAR_SEARCH','Clear Search'))
			)
		);
		$form->setFormMethod('get');

		return $form;
	}

	function ResultsForm($searchCriteria) {
		if($searchCriteria instanceof SS_HTTPRequest) $searchCriteria = $searchCriteria->getVars();

		$form = new Form($this, 'ResultsForm',
			new FieldSet(
				$searchfield = $this->getSearchField()
			),
			new FieldSet()
		);
		$searchfield->setSearchCriteria($searchCriteria);

		return $form;
	}

	public function UpdateForm($otherObject = null) {
		if($otherObject instanceof SS_HTTPRequest) $otherObject = DataObject::get_by_id($this->otherClass, (int)$otherObject->param('ItemID'));
		if(!$otherObject) $otherObject = singleton($this->otherClass);
		$validator = $otherObject->hasMethod('getCMSValidator') ? $otherObject->getCMSValidator() : new RequiredFields();

		$fields = $otherObject->hasMethod('getCMSFields_forPopup') ? $otherObject->getCMSFields_forPopup() : $otherObject->scaffoldFormFields(array('ajaxSafe' => true));

		// set association on has_one / has_many item
		$remotejoinfield = $this->parent->getRemoteJoinField($this->name);
		$fields->replaceField($remotejoinfield, new HiddenField($remotejoinfield, $remotejoinfield, $this->parent->ID));
		$fields->push(new HiddenField('ItemID', 'ItemID' , $otherObject->ID));

		$form = new Form($this, 'UpdateForm',
			$fields,
			new FieldSet(
				new FormAction('Update', _t('MemberTableField.EDIT', 'Save')),
				new ResetFormAction('ClearEdit', _t('ModelAdmin.CLEAR_EDIT','Clear Form'))
			),
			$validator
		);
		$form->setFormMethod('get');

		return $form;
	}

	function Update($data,$form) {
		$otherObject = DataObject::get_by_id($this->otherClass, (int)$data['ItemID']);
		if(!$otherObject) $otherObject = new $this->otherClass();
		$form->saveInto($otherObject);
		$otherObject->write();
		if($this instanceof ManyManyPickerField) {
			$assoc = $this->name;
			$this->parent->$assoc()->add($otherObject);
		} else if($this instanceof HasOnePickerField) {
			$assoc = $this->name . 'ID';
			if($this->options['ManagePicked'] && $this->options['DeleteRemoved'] && $this->parent->$assoc = $otherObject->ID) {
				$old = DataObject::get_by_id($this->otherClass, (int)$this->parent->$assoc);
				if($old) $old->delete();
			}
			$this->parent->$assoc = $otherObject->ID;
			$this->parent->write();
		}

		return Director::is_ajax() ? $this->FieldHolder() : Director::redirectBack();
	}

	public function Edit($otherObject) {
		if($otherObject instanceof SS_HTTPRequest) $otherObject = DataObject::get_by_id($this->otherClass, (int)$otherObject->param('ItemID'));
		if(!$otherObject) $otherObject = singleton($this->otherClass);

		$form = $this->UpdateForm($otherObject);
		$form->loadDataFrom($otherObject);

		return $this->customise(array(
			'Form' => $form,
		))->renderWith('HasManyPickerField_Edit');
	}

	public function Search($searchCriteria) {
		if($searchCriteria instanceof SS_HTTPRequest) $searchCriteria = $searchCriteria->getVars();

		$form = $this->SearchForm();
		$form->loadDataFrom($searchCriteria);

		$resform = $this->ResultsForm($searchCriteria);

		return $this->customise(array(
			'Form' => $form,
			'Results' => $resform
		))->renderWith('HasManyPickerField_Search');
	}

	public function Add($data, $item) {
		$accessor = $this->name;
		$this->parent->$accessor()->add($item);
		$this->parent->write();

		return Director::is_ajax() ? $this->FieldHolder() : Director::redirectBack();
	}

	public function Remove($data, $item) {

		if($this->options['ManagePicked'] && $this->options['DeleteRemoved']) {
			$item->delete();
		} else {
			$accessor = $this->name;
			$this->parent->$accessor()->remove($item);
			$this->parent->write();
		}

		return Director::is_ajax() ? $this->FieldHolder() : Director::redirectBack();
	}

}

class HasManyPickerField_SearchField extends SearchItemSetField {

	public static $item_default_action = 'Choose';

	public function __construct($parent) {
		$this->parent = $parent;
		parent::__construct($parent->getOtherClass(), 'Results');
	}

	public function getItemsQuery() {
		$query = parent::getItemsQuery();

		if ($filter = $this->parent->getOption('ExtraFilter')) {
			$query->where($filter);
		}

		if (!$this->parent->getOption('ShowPickedInSearch')) {
			$id     = sprintf('"%s"."ID"', $this->parent->getOtherClass());
			$ignore = $this->parent->getItemsQuery();
			$ignore->select($id);

			if ($ids = $ignore->execute()->column()) {
				$query->where("$id NOT IN (" . implode(', ', $ids) . ')');
			}
		}

		return $query;
	}

	public function Choose($data, $item) {
		return $this->parent->Add($data, $item);
	}

}
