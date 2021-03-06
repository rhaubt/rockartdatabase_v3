<?php

namespace Wikibase\Lib\Formatters;

use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Int32EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikimedia\Assert\Assert;

/**
 * Wrapper class giving ability to replace EntityIdFormatter in production in a controlled manner.
 * Depending on the provided $maxEntityId, formatting will be delegated to either `$targetFormatter`
 * if given entity ID is <= than $maxEntityId, or to `$fallbackFormatter` otherwise.
 *
 * @license GPL-2.0-or-later
 */
class ControlledFallbackEntityIdFormatter implements EntityIdFormatter {

	use LoggerAwareTrait;

	/**
	 * @var int
	 */
	private $maxEntityId;

	/**
	 * @var EntityIdFormatter
	 */
	private $targetFormatter;

	/**
	 * @var EntityIdFormatter
	 */
	private $fallbackFormatter;

	/**
	 * @var StatsdDataFactoryInterface
	 */
	private $statsdDataFactory;

	/**
	 * @var string
	 */
	private $statsPrefix;

	/**
	 * @param int $maxEntityId
	 * @param EntityIdFormatter $targetFormatter
	 * @param EntityIdFormatter $fallbackFormatter
	 * @param StatsdDataFactoryInterface $statsdDataFactory
	 * @param string $statsPrefix
	 */
	public function __construct(
		$maxEntityId,
		EntityIdFormatter $targetFormatter,
		EntityIdFormatter $fallbackFormatter,
		StatsdDataFactoryInterface $statsdDataFactory,
		$statsPrefix
	) {
		Assert::parameterType( 'string', $statsPrefix, '$statsPrefix' );

		$this->maxEntityId = $maxEntityId;
		$this->targetFormatter = $targetFormatter;
		$this->fallbackFormatter = $fallbackFormatter;
		$this->logger = new NullLogger();
		$this->statsdDataFactory = $statsdDataFactory;
		$this->statsPrefix = $statsPrefix;
	}

	public function formatEntityId( EntityId $value ) {
		if ( $value instanceof Int32EntityId && $value->getNumericId() <= $this->maxEntityId ) {
			try {
				$formatEntityId = $this->targetFormatter->formatEntityId( $value );
				$this->statsdDataFactory->increment( $this->statsPrefix . 'targetFormatterCalled' );
				return $formatEntityId;
			} catch ( \Exception $e ) { //TODO: Catch Throwable once we move to php7
				$this->logTargetFormatterFailure( $value, $e );

				return $this->formatUsingFallbackFormatter( $value );
			}
		}

		return $this->formatUsingFallbackFormatter( $value );
	}

	/**
	 * @param EntityId $value
	 * @return string
	 */
	private function formatUsingFallbackFormatter( EntityId $value ) {
		$formatEntityId = $this->fallbackFormatter->formatEntityId( $value );
		$this->statsdDataFactory->increment( $this->statsPrefix . 'fallbackFormatterCalled' );
		return $formatEntityId;
	}

	/**
	 * @param EntityId $value
	 * @param \Exception $e
	 */
	private function logTargetFormatterFailure( EntityId $value, \Exception $e ) {
		$this->logger->error(
			'Failed to format entity ID. Using fallback formatter.'
			. ' Error: {exception_message}',
			[
				'entityId' => $value->getSerialization(),
				'exception' => $e,
			]
		);
		$this->statsdDataFactory->increment( $this->statsPrefix . 'targetFormatterFailed' );
	}

}
