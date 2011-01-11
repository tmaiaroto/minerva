<?php
/**
 * Form helper tests file
 *
 * @copyright     Copyright 2010, alkemann
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 *
 */

namespace al13_helpers\tests\cases\extensions\helper;

use \lithium\data\entity\Record;
use \al13_helpers\extensions\helper\Form;
use \lithium\tests\mocks\template\helper\MockFormRenderer;

class FormTest extends \lithium\test\Unit {

	public function setUp() {
		$this->form = new Form(array('context' => new MockFormRenderer()));
	}

	public function testAsField() {
		$result = $this->form->fields('name', array('template' => '{:input}', 'label' => false));
		$expected = '<input type="text" name="name" id="name" />';
		$this->assertEqual($result, $expected);
	}

	public function testCheckbox() {
		$result = $this->form->fields(array(
			'student' => array(
				'type' => 'checkbox'
			)
		));
		$expected = array(
			array('div' => array('class' => 'input checkbox')),
				array('input' => array('type' => 'hidden', 'name' => 'student', 'value' => 0)),
				array('input' => array('type' => 'checkbox', 'name' => 'student', 'value' => 1, 'id' => 'student')),
				array('label' => array('for' => 'student')),
					'Student',
				'/label',
			'/div',
		);
		$this->assertTags($result, $expected);
	}

	public function testMultipleFieldsWithOptions() {
		$result = $this->form->fields(array(
			'name',
			'password',
			'surname' => array('label' => false),
			'present' => array('type' => 'checkbox')
		), array(
			'template' => '<li{:wrap}>{:label}{:input}{:error}</li>'
		));
		$expected = array(
			array('li' => array('class' => 'input')),
				array('label' => array('for' => 'name')),
					'Name',
				'/label',
				array('input' => array('type' => 'text', 'name' => 'name', 'id' => 'name')),
			'/li',
			array('li' => array('class' => 'input')),
				array('label' => array('for' => 'password')),
					'Password',
				'/label',
				array('input' => array('type' => 'password', 'name' => 'password', 'id' => 'password')),
			'/li',
			array('li' => array('class' => 'input')),
				array('input' => array('type' => 'text', 'name' => 'surname', 'id' => 'surname')),
			'/li',
			array('li' => array('class' => 'input')),
				array('label' => array('for' => 'present')),
					'Present',
				'/label',
				array('input' => array('type' => 'hidden', 'value' => 0, 'name' => 'present')),
				array('input' => array(
					'type' => 'checkbox', 'value' => 1, 'name' => 'present', 'id' => 'present'
				)),
			'/li',
		);
		$this->assertTags($result, $expected);
	}

	public function testRadio() {
		$user = new Record();
		$user->gender = 'f';
		$this->form->create($user);

		$result = $this->form->radio('gender', array('value' => 'm'), array());
		$expected = array('input' => array('type' => 'radio', 'name' => 'gender', 'value' => 'm'));
		$this->assertTags($result, $expected);

		$result = $this->form->radio('gender', array('value' => 'f'), array());
		$expected = array('input' => array(
			'type' => 'radio', 'name' => 'gender', 'value' => 'f', 'checked' => 'checked'
		));
		$this->assertTags($result, $expected);


		$result = $this->form->radio('gender', array(), array('m' => 'Male', 'f' => 'Female'));
		$expected = array(
			array('div' => array('class' => 'radio')),
				array('input' => array(
					'type' => 'radio', 'name' => 'gender', 'id' => 'gender-Male', 'value' => 'm'
				)),
				array('label' => array('for' => 'gender-Male')),
					'Male',
				'/label',
			'/div',
			array('div' => array('class' => 'radio')),
				array('input' => array(
					'type' => 'radio', 'name' => 'gender', 'value' => 'f',
					'id' => 'gender-Female', 'checked' => 'checked'
				)),
				array('label' => array('for' => 'gender-Female')),
					'Female',
				'/label',
			'/div',
		);
		$this->assertTags($result, $expected);
	}
}

?>