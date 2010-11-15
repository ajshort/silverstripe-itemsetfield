<?php

class ItemSetFieldTest extends FunctionalTest {

	static $fixture_file = 'sapphire/tests/security/GroupTest.yml';

	function testManymanypickerfieldShowsCurrentRelationsCorrectly() {

		// field reflects current relations correctly
		$admins = $this->objFromFixture('Group', 'admingroup');
		$field = new ManyManyPickerField($admins, 'Members', 'Members');
		$this->assertDOSContains(
			array(
				array('FirstName' => 'Admin'),
				array('FirstName' => 'All Group User'),
			),
			$field->Items()
		);
	}
	
	function testSearchField() {
		$admins = $this->objFromFixture('Group', 'admingroup');
		$controller = new TestGroupController();
		$form = $controller->getEditForm($admins);
		$field = $form->Fields()->fieldByName('Members');
		$raw = $field->Search(new SS_HTTPRequest('GET', $field->Link('Search'), array('FirstName' => 'Child')));

		$this->assertContains('Child Group User', $raw, 'DataObject found.');
	}
}

class TestGroupController extends Controller implements TestOnly {

	function getEditForm($group) {
		return new Form($this, 'getEditForm', new FieldSet(new ManyManyPickerField($group, 'Members', 'Members')), new FieldSet());
	}

	function Link() {
		return 'SomeTestController';
	}
}