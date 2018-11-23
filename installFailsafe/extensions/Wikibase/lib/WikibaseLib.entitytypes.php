<?php

/**
 * Definition of base entity types for use with Wikibase.
 *
 * @note: When adding entity types here, also add the corresponding information to
 * repo/WikibaseRepo.entitytypes.php
 *
 * @note: This is bootstrap code, it is executed for EVERY request. Avoid instantiating
 * objects or loading classes here!
 *
 * @see docs/entitytypes.wiki
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */

use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\Diff\ItemDiffer;
use Wikibase\DataModel\Services\Diff\ItemPatcher;
use Wikibase\DataModel\Services\Diff\PropertyDiffer;
use Wikibase\DataModel\Services\Diff\PropertyPatcher;

return [
	'item' => [
		'serializer-factory-callback' => function( SerializerFactory $serializerFactory ) {
			return $serializerFactory->newItemSerializer();
		},
		'deserializer-factory-callback' => function( DeserializerFactory $deserializerFactory ) {
			return $deserializerFactory->newItemDeserializer();
		},
		'entity-id-pattern' => ItemId::PATTERN,
		'entity-id-builder' => function( $serialization ) {
			return new ItemId( $serialization );
		},
		'entity-id-composer-callback' => function( $repositoryName, $uniquePart ) {
			return ItemId::newFromRepositoryAndNumber( $repositoryName, $uniquePart );
		},
		'entity-differ-strategy-builder' => function() {
			return new ItemDiffer();
		},
		'entity-patcher-strategy-builder' => function() {
			return new ItemPatcher();
		},
	],
	'property' => [
		'serializer-factory-callback' => function( SerializerFactory $serializerFactory ) {
			return $serializerFactory->newPropertySerializer();
		},
		'deserializer-factory-callback' => function( DeserializerFactory $deserializerFactory ) {
			return $deserializerFactory->newPropertyDeserializer();
		},
		'entity-id-pattern' => PropertyId::PATTERN,
		'entity-id-builder' => function( $serialization ) {
			return new PropertyId( $serialization );
		},
		'entity-id-composer-callback' => function( $repositoryName, $uniquePart ) {
			return PropertyId::newFromRepositoryAndNumber( $repositoryName, $uniquePart );
		},
		'entity-differ-strategy-builder' => function() {
			return new PropertyDiffer();
		},
		'entity-patcher-strategy-builder' => function() {
			return new PropertyPatcher();
		},
	]
];
