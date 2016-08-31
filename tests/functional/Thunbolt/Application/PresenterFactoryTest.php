<?php

namespace Thunbolt\Application;

use WebChemistry\Test\TMethods;

class PresenterFactoryTest extends \Codeception\Test\Unit {

	use TMethods;

	/** @var PresenterFactory */
	private $factory;

	protected function _before() {
		$this->factory = new PresenterFactory();
	}

	protected function _after() {
	}

	public function testFormatPresenterClass() {
		$this->assertSame('AppBundle\\FrontModule\\HomepagePresenter', $this->factory->formatPresenterClass('Front:Homepage'));
		$this->assertSame('ShopBundle\\FrontModule\\HomepagePresenter', $this->factory->formatPresenterClass('Front:Shop:Homepage'));
		$this->assertSame('AppBundle\\AdminModule\\HomepagePresenter', $this->factory->formatPresenterClass('Admin:Homepage'));
		$this->assertSame('ShopBundle\\AdminModule\\HomepagePresenter', $this->factory->formatPresenterClass('Admin:Shop:Homepage'));
	}

	public function testUnformatPresenterClass() {
		$this->assertSame('Front:Homepage', $this->factory->unformatPresenterClass('AppBundle\\FrontModule\\HomepagePresenter'));
		$this->assertSame('Front:Shop:Homepage', $this->factory->unformatPresenterClass('ShopBundle\\FrontModule\\HomepagePresenter'));
		$this->assertSame('Admin:Homepage', $this->factory->unformatPresenterClass('AppBundle\\AdminModule\\HomepagePresenter'));
		$this->assertSame('Admin:Shop:Homepage', $this->factory->unformatPresenterClass('ShopBundle\\AdminModule\\HomepagePresenter'));
	}

	public function testInvalidFormatPresenterClass() {
		$this->assertThrowException(function () {
			$this->factory->formatPresenterClass('Front');
		}, PresenterFactoryException::class);

		$this->assertThrowException(function () {
			$this->factory->formatPresenterClass('Front:Shop:Next:Homepage');
		}, PresenterFactoryException::class);

		$this->assertThrowException(function () {
			$this->factory->formatPresenterClass('Not:Homepage');
		}, PresenterFactoryException::class);
	}

	public function testInvalidUnformatPresenterClass() {
		$this->assertThrowException(function () {
			$this->factory->unformatPresenterClass('ShopBundle\\NotModule\\HomepagePresenter');
		}, PresenterFactoryException::class);

		$this->assertThrowException(function () {
			$this->factory->unformatPresenterClass('ShopBundle\\AdminModule\\NextModule\\HomepagePresenter');
		}, PresenterFactoryException::class);

		$this->assertThrowException(function () {
			$this->factory->unformatPresenterClass('HomepagePresenter');
		}, PresenterFactoryException::class);

		$this->assertThrowException(function () {
			$this->factory->unformatPresenterClass('AppBundle\\HomepagePresenter');
		}, PresenterFactoryException::class);
	}

}
