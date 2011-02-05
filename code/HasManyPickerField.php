<?php
/**
 * A picker field with searching for a has_many relationship.
 */
class HasManyPickerField extends ItemSetField {

	public static $default_options = array(
		'ShowPickedInSearch' => true,
		'ExtraFilter'        => false
	);

	public static $actions = array(
		'Search' => 'search'
	);

	public static $item_actions = array(
		'Remove'
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

	public function getItemsQuery() {
		return $this->parent->getComponentsQuery($this->name);
	}

	public function saveInto(DataObject $record) {
		$record->{$this->name}()->setByIDList($this->value);
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
		$accessor = $this->name;
		$this->parent->$accessor()->remove($item);
		$this->parent->write();

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
