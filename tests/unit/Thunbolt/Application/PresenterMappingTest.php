<?php
namespace Thunbolt\Application;

class PresenterMappingTest extends \Codeception\Test\Unit {

	protected function _before() {
	}

	protected function _after() {
	}

	public function testFormatAppBundle() {
		$mapping = new DefaultPresenterMapping('Front');
		$this->assertSame('AppBundle\\FrontModule\\HomepagePresenter', $mapping->format(['Homepage']));
	}

	public function testFormat() {
		$mapping = new DefaultPresenterMapping('Front');
		$this->assertSame('ShopBundle\\FrontModule\\BasketPresenter', $mapping->format(explode(':', 'Shop:Basket')));
	}

	public function testUnformatAppBundle() {
		$mapping = new DefaultPresenterMapping('Front');
		$this->assertSame('Front:Homepage', $mapping->unformat('AppBundle\\FrontModule\\HomepagePresenter'));
	}

	public function testUnformat() {
		$mapping = new DefaultPresenterMapping('Front');
		$this->assertSame('Front:Shop:Basket', $mapping->unformat('ShopBundle\\FrontModule\\BasketPresenter'));
	}

	public function testUnformatInvalid() {
		$mapping = new DefaultPresenterMapping('Front');
		$this->assertNull($mapping->unformat('ShopBundle\\AdminModule\\BasketPresenter'));
	}

}
