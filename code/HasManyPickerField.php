<?php
/**
 * A picker field with searching for a has_many relationship.
 */
class HasManyPickerField extends ItemSetField {

	public static $default_options = array(
		'Searchable'         => true,
		'Sortable'           => false,
		'SortableField'      => 'Sort',
		'ExtraFilter'        => false,
		'ShowPickedInSearch' => true,
		'AllowCreate'        => false,
		'AllowEdit'          => false,
		'AllowRemove'        => true,
		'AllowDelete'        => false,
		'FieldsMethod'       => 'getFrontEndFields',
		'ValidatorMethod'    => 'getValidator'
	);

	protected $parent;
	protected $otherClass;
	protected $searchField;
	protected $searchFieldClass = 'ManyManyPickerField_SearchField';

	public function __construct($parent, $name, $title = null, $options = null) {
		$this->parent     = $parent;
		$this->otherClass = $parent->has_many($name);

		parent::__construct($name, $title, $options);
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

	public function getItemById($id) {
		return DataObject::get_by_id($this->getOtherClass(), $id);
	}

	public function Actions() {
		$actions = parent::Actions();

		if ($this->getOption('AllowCreate') && singleton($this->getOtherClass())->canCreate()) {
			$actions->push(new ArrayData(array(
				'Name' => 'Create',
				'Link' => Controller::join_links($this->Link(), 'CreateNew')
			)));
		}

		if ($this->getOption('Searchable')) {
			$actions->push(new ArrayData(array(
				'Name'       => 'Search',
				'Link'       => Controller::join_links($this->Link(), 'Search'),
				'ExtraClass' => 'search'
			)));
		}

		return $actions;
	}

	public function ItemActions($item) {
		$actions = parent::ItemActions($item);

		if ($this->getOption('AllowEdit') && $item->canEdit()) {
			$actions->push(new ItemSetField_Action(
				$this, 'Edit', 'Edit'
			));
		}

		if ($this->getOption('AllowRemove')) {
			$actions->push(new ItemSetField_Action(
				$this, 'Remove', 'Remove', true
			));
		}

		if ($this->getOption('AllowDelete') && $item->canDelete()) {
			$actions->push(new ItemSetField_Action(
				$this, 'Delete', 'Delete', true
			));
		}

		return $actions;
	}

	public function saveInto(DataObject $record) {
		if (strstr($this->originalName, '.')) {
			$set = $record;
			foreach (explode('.', $this->originalName) as $field) {
				$set = $set->{$field}();
			}
		} else {
			$set = $record->{$this->name}();
		}

		if (!$this->getOption('Sortable') || !count($set)) {
			return;
		}

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
		$context = singleton($this->getOtherClass())->getDefaultSearchContext();
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

	public function CreateNew() {
		return $this->CreateForm()->forTemplate();
	}

	public function CreateForm() {
		$sng       = singleton($this->getOtherClass());
		$fields    = $sng->{$this->getOption('FieldsMethod')}();
		$validator = $this->getOption('ValidatorMethod');

		$validator = $sng->hasMethod($validator) ? $sng->$validator() : new RequiredFields();
		$validator->setJavascriptValidationHandler('none');

		$fields->removeByName($this->parent->getRemoteJoinField($this->name));

		$actions = new FieldSet(
			new FormAction('doCreate', _t('ItemSetField.CREATE', 'Create'))
		);

		return new Form($this, 'CreateForm', $fields, $actions, $validator);
	}

	public function doCreate($data, $form) {
		if (!singleton($this->getOtherClass())->canCreate()) {
			$this->httpError(403);
		}

		$class  = $this->getOtherClass();
		$record = new $class;
		$form->saveInto($record);

		try {
			$record->write();
			$this->Add($data, $record);
		} catch (ValidationException $e) {
			$form->sessionMessage($e->getResult()->message(), 'bad');
			return Director::is_ajax() ? $form->forTemplate() : Director::redirectBack();
		}

		return Director::is_ajax() ? $this->FieldHolder() : Director::redirectBack();
	}

	public function Add($data, $item) {
		$accessor = $this->name;
		$this->parent->$accessor()->add($item);
		$this->parent->write();

		return Director::is_ajax() ? $this->FieldHolder() : Director::redirectBack();
	}

	public function Remove($data, $item) {
		$accessor = $this->name;
		$this->parent->$accessor()->remove($item);
		$this->parent->write();

		return Director::is_ajax() ? $this->FieldHolder() : Director::redirectBack();
	}

	public function Delete($data, $item) {
		if (!$item->canDelete()) {
			$this->httpError(403);
		}

		$this->parent->{$this->name}()->remove($item);
		$item->delete();

		return Director::is_ajax() ? $this->FieldHolder() : Director::redirectBack();
	}

	public function Edit($data, $item) {
		$form = $this->EditForm($item);

		$form->loadDataFrom($item);
		$form->Fields()->push(new HiddenField('ItemID', null, $item->ID));

		return $form->forTemplate();
	}

	public function EditForm() {
		$item      = singleton($this->getOtherClass());
		$fields    = $item->{$this->getOption('FieldsMethod')}();
		$validator = $this->getOption('ValidatorMethod');

		$validator = $item->hasMethod($validator) ? $item->$validator() : new RequiredFields();
		$validator->setJavascriptValidationHandler('none');

		$fields->removeByName($this->parent->getRemoteJoinField($this->name));

		$actions = new FieldSet(
			new FormAction('doEdit', _t('ItemSetField.SAVE', 'Save'))
		);

		return new Form($this, 'EditForm', $fields, $actions, $validator);
	}

	public function doEdit($data, $form) {
		$id   = (int) $data['ItemID'];
		$item = DataObject::get_by_id($this->getOtherClass(), $id);

		if (!$item) {
			$this->httpError(404);
		}

		if (!$item->canEdit()) {
			$this->httpError(403);
		}

		try {
			$form->saveInto($item);
			$item->write();
		} catch (ValidationException $e) {
			$form->sessionMessage($e->getResult()->message(), 'bad');
			$form->Fields()->push(new HiddenField('ItemID', null, $id));

			return Director::is_ajax() ? $form->forTemplate() : Director::redirectBack();
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
