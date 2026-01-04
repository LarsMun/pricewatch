<?php

namespace App\Tests\Unit\Entity;

use App\Entity\ProductWatch;
use App\Enum\CheckMethod;
use PHPUnit\Framework\TestCase;

class ProductWatchTest extends TestCase
{
    // === updatePrice Tests (Price Debounce Logic) ===

    public function testUpdatePriceFirstTime(): void
    {
        $watch = new ProductWatch();
        $this->assertNull($watch->getCurrentPrice());

        $changed = $watch->updatePrice('19.99');

        $this->assertTrue($changed);
        $this->assertSame('19.99', $watch->getCurrentPrice());
        $this->assertNull($watch->getPreviousPrice());
    }

    public function testUpdatePriceSameAsCurrentNoChange(): void
    {
        $watch = new ProductWatch();
        $watch->updatePrice('19.99');

        $changed = $watch->updatePrice('19.99');

        $this->assertFalse($changed);
        $this->assertSame('19.99', $watch->getCurrentPrice());
    }

    public function testUpdatePriceFlappingIgnored(): void
    {
        $watch = new ProductWatch();

        // First price
        $watch->updatePrice('19.99');
        $this->assertSame('19.99', $watch->getCurrentPrice());

        // Price changes to 24.99
        $watch->updatePrice('24.99');
        $this->assertSame('24.99', $watch->getCurrentPrice());
        $this->assertSame('19.99', $watch->getPreviousPrice());

        // Price "flaps" back to 19.99 - should be ignored
        $changed = $watch->updatePrice('19.99');
        $this->assertFalse($changed);
        $this->assertSame('24.99', $watch->getCurrentPrice()); // Still 24.99
    }

    public function testUpdatePriceRealChange(): void
    {
        $watch = new ProductWatch();
        $watch->updatePrice('19.99');
        $watch->updatePrice('24.99');

        // New different price (not previous)
        $changed = $watch->updatePrice('29.99');

        $this->assertTrue($changed);
        $this->assertSame('29.99', $watch->getCurrentPrice());
        $this->assertSame('24.99', $watch->getPreviousPrice());
    }

    // === scheduleNextCheck Tests ===

    public function testScheduleNextCheckWithinRange(): void
    {
        $watch = new ProductWatch();
        $now = new \DateTimeImmutable();

        $watch->scheduleNextCheck();

        $nextCheck = $watch->getNextCheckAt();
        $this->assertNotNull($nextCheck);

        // Should be between 12 and 13 hours from now
        $minExpected = $now->modify('+12 hours');
        $maxExpected = $now->modify('+13 hours');

        $this->assertGreaterThanOrEqual($minExpected, $nextCheck);
        $this->assertLessThanOrEqual($maxExpected, $nextCheck);
    }

    // === Failure Counter Tests ===

    public function testIncrementFailures(): void
    {
        $watch = new ProductWatch();
        $this->assertSame(0, $watch->getConsecutiveFailures());

        $watch->incrementFailures();
        $this->assertSame(1, $watch->getConsecutiveFailures());

        $watch->incrementFailures();
        $this->assertSame(2, $watch->getConsecutiveFailures());
    }

    public function testResetFailures(): void
    {
        $watch = new ProductWatch();
        $watch->incrementFailures();
        $watch->incrementFailures();
        $watch->incrementFailures();

        $watch->resetFailures();

        $this->assertSame(0, $watch->getConsecutiveFailures());
    }

    public function testHasReachedFailureThresholdAt5(): void
    {
        $watch = new ProductWatch();

        for ($i = 0; $i < 5; $i++) {
            $watch->incrementFailures();
        }

        $this->assertTrue($watch->hasReachedFailureThreshold());
    }

    public function testHasReachedFailureThresholdBelow5(): void
    {
        $watch = new ProductWatch();

        for ($i = 0; $i < 4; $i++) {
            $watch->incrementFailures();
        }

        $this->assertFalse($watch->hasReachedFailureThreshold());
    }

    public function testHasReachedFailureThresholdAbove5(): void
    {
        $watch = new ProductWatch();

        for ($i = 0; $i < 7; $i++) {
            $watch->incrementFailures();
        }

        $this->assertTrue($watch->hasReachedFailureThreshold());
    }

    // === URL and Domain Tests ===

    public function testSetUrlExtractsDomain(): void
    {
        $watch = new ProductWatch();
        $watch->setUrl('https://www.bol.com/nl/product/123');

        $this->assertSame('https://www.bol.com/nl/product/123', $watch->getUrl());
        $this->assertSame('www.bol.com', $watch->getDomain());
    }

    public function testSetUrlExtractsDomainWithPort(): void
    {
        $watch = new ProductWatch();
        $watch->setUrl('http://localhost:8080/page');

        $this->assertSame('localhost', $watch->getDomain());
    }

    // === Pause/Resume Tests ===

    public function testPauseAndResume(): void
    {
        $watch = new ProductWatch();
        $this->assertTrue($watch->isActive());

        $watch->pause();
        $this->assertFalse($watch->isActive());

        $watch->resume();
        $this->assertTrue($watch->isActive());
    }

    public function testResumeSchedulesNextCheck(): void
    {
        $watch = new ProductWatch();
        $watch->pause();

        $oldNextCheck = $watch->getNextCheckAt();
        sleep(1); // Ensure time difference

        $watch->resume();

        // Next check should be rescheduled
        $this->assertNotEquals($oldNextCheck, $watch->getNextCheckAt());
    }

    // === CheckMethod Tests ===

    public function testDefaultCheckMethodIsHttp(): void
    {
        $watch = new ProductWatch();
        $this->assertSame(CheckMethod::HTTP, $watch->getCheckMethod());
    }

    public function testSetCheckMethodToBrowser(): void
    {
        $watch = new ProductWatch();
        $watch->setCheckMethod(CheckMethod::BROWSER);

        $this->assertSame(CheckMethod::BROWSER, $watch->getCheckMethod());
    }

    // === LastErrorMessage Tests ===

    public function testSetAndGetLastErrorMessage(): void
    {
        $watch = new ProductWatch();
        $this->assertNull($watch->getLastErrorMessage());

        $watch->setLastErrorMessage('Connection timeout');
        $this->assertSame('Connection timeout', $watch->getLastErrorMessage());

        $watch->setLastErrorMessage(null);
        $this->assertNull($watch->getLastErrorMessage());
    }

    // === Original Price Tests ===

    public function testOriginalPriceSetOnFirstUpdate(): void
    {
        $watch = new ProductWatch();
        $this->assertNull($watch->getOriginalPrice());

        $watch->setOriginalPrice('99.99');
        $this->assertSame('99.99', $watch->getOriginalPrice());
    }
}
