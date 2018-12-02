<?php

namespace ActivityPubTest\Type;

use ActivityPub\Type\Core\Activity;
use ActivityPub\Type\Core\Collection;
use ActivityPub\Type\Core\CollectionPage;
use ActivityPub\Type\Core\IntransitiveActivity;
use ActivityPub\Type\Core\Link;
use ActivityPub\Type\Core\ObjectType;
use ActivityPub\Type\Core\OrderedCollection;
use ActivityPub\Type\Core\OrderedCollectionPage;
use ActivityPub\Type\Extended\Actor\Application;
use ActivityPub\Type\Extended\Actor\Group;
use ActivityPub\Type\Extended\Actor\Organization;
use ActivityPub\Type\Extended\Actor\Person;
use ActivityPub\Type\Extended\Actor\Service;
use ActivityPub\Type\Extended\Activity\Accept;
use ActivityPub\Type\Extended\Activity\Add;
use ActivityPub\Type\Extended\Activity\Announce;
use ActivityPub\Type\Extended\Activity\Arrive;
use ActivityPub\Type\Extended\Activity\Block;
use ActivityPub\Type\Extended\Activity\Create;
use ActivityPub\Type\Extended\Activity\Delete;
use ActivityPub\Type\Extended\Activity\Dislike;
use ActivityPub\Type\Extended\Activity\Flag;
use ActivityPub\Type\Extended\Activity\Follow;
use ActivityPub\Type\Extended\Activity\Ignore;
use ActivityPub\Type\Extended\Activity\Invite;
use ActivityPub\Type\Extended\Activity\Join;
use ActivityPub\Type\Extended\Activity\Leave;
use ActivityPub\Type\Extended\Activity\Like;
use ActivityPub\Type\Extended\Activity\Listen;
use ActivityPub\Type\Extended\Activity\Move;
use ActivityPub\Type\Extended\Activity\Offer;
use ActivityPub\Type\Extended\Activity\Question;
use ActivityPub\Type\Extended\Activity\Read;
use ActivityPub\Type\Extended\Activity\Reject;
use ActivityPub\Type\Extended\Activity\Remove;
use ActivityPub\Type\Extended\Activity\TentativeAccept;
use ActivityPub\Type\Extended\Activity\TentativeReject;
use ActivityPub\Type\Extended\Activity\Travel;
use ActivityPub\Type\Extended\Activity\Undo;
use ActivityPub\Type\Extended\Activity\Update;
use ActivityPub\Type\Extended\Activity\View;
use ActivityPub\Type\Extended\Object\Article;
use ActivityPub\Type\Extended\Object\Audio;
use ActivityPub\Type\Extended\Object\Document;
use ActivityPub\Type\Extended\Object\Event;
use ActivityPub\Type\Extended\Object\Image;
use ActivityPub\Type\Extended\Object\Mention;
use ActivityPub\Type\Extended\Object\Note;
use ActivityPub\Type\Extended\Object\Page;
use ActivityPub\Type\Extended\Object\Place;
use ActivityPub\Type\Extended\Object\Profile;
use ActivityPub\Type\Extended\Object\Relationship;
use ActivityPub\Type\Extended\Object\Tombstone;
use ActivityPub\Type\Extended\Object\Video;
use ActivityPub\Type\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
	/**
	 * Valid scenarios provider
	 */
	public function getValidAttributesScenarios()
	{
		# TypeClass, property, value
		return [
			[Activity::class, 'actor', 'https://example.com/bob'			], # Set actor as URL
			[Activity::class, 'actor', '{
											"type": "Person",
											"id": "http://sally.example.org",
											"summary": "Sally"
										}'									], # Set actor as an Actor type, JSON encoded
			[Activity::class, 'actor', '[
											"http://joe.example.org",
											{
												"type": "Person",
												"id": "http://sally.example.org",
												"name": "Sally"
											}
										]'									], # Set actor as multiple actors, JSON encoded
			[Image::class, 'attributedTo', ' [
												{
													"type": "Person",
													"name": "Sally"
												}
											]'								], # Set attributedTo with an array of persons
			[Image::class, 'attributedTo', '
												{
													"type": "Person",
													"name": "Sally"
												}
											'								], # Set attributedTo with a single actor
			[Image::class, 'attributedTo', '
												{
													"type": "Link",
													"href": "http://joe.example.org"
												}
											'								], # Set attributedTo with a Link
			[Image::class, 'attributedTo', ' [
												"http://sally.example.org",
												{
													"type": "Person",
													"name": "Sally"
												}
											]'								], # Set attributedTo with an array of mixed URL and persons
			[Note::class, 'audience', ' [
												{
													"type": "Person",
													"name": "Sally"
												}
											]'								], # Set audience with an array of persons
			[Note::class, 'audience', '
												{
													"type": "Person",
													"name": "Sally"
												}
											'								], # Set audience with a single actor
			[Note::class, 'audience', '
												{
													"type": "Link",
													"href": "http://joe.example.org"
												}
											'								], # Set audience with a Link
			[Note::class, 'audience', ' [
												"http://sally.example.org",
												{
													"type": "Person",
													"name": "Sally"
												}
											]'								], # Set attributedTo with an array of mixed URL and persons
			[Note::class, 'attachment', ' [
													{
														"type": "Image",
														"content": "This is what he looks like.",
														"url": "http://example.org/cat.jpeg"
													}
												]'							], # Set attachment	with an ObjectType
			[Note::class, 'attachment', ' [
													{
														"type": "Link",
														"href": "http://example.org/cat.jpeg"
													}
												]'							], # Set attachment	with an Link
			[Note::class, 'attachment', '[
											"http://example.org/cat.jpeg"
										]'									], # Set attachment with an indirect reference
			[ObjectType::class, 'attachment', ' [
													{
														"type": "Image",
														"content": "This is what he looks like.",
														"url": "http://example.org/cat.jpeg"
													}
												]'							], # Set attachment	
			[Place::class, 'accuracy', 100							], # Set accuracy (int) 
			[Place::class, 'accuracy', 0							], # Set accuracy (int)
			[Place::class, 'accuracy', '0'							], # Set accuracy (numeric int) 
			[Place::class, 'accuracy', '0.5'						], # Set accuracy (numeric float) 
			[Place::class, 'altitude', 0.5							], # Set altitude (float)
			[Question::class, 'anyOf', '[
											{
												"type": "Note",
												"name": "Option A"
											},
											{
												"type": "Note",
												"name": "Option B"
											}
										]'							], # Set anyOf choices 

		];
	}

	/**
	 * Exception scenarios provider
	 */
	public function getExceptionScenarios()
	{
		# TypeClass, property, value
		return [
			[Activity::class, 'actor', 'https:/example.com/bob'		], # Set actor as malformed URL
			[Activity::class, 'actor', 'bob'						], # Set actor as not allowed string
			[Activity::class, 'actor', 42							], # Set actor as not allowed type
			[Activity::class, 'actor', '{}'							], # Set actor as a JSON malformed string
			[Activity::class, 'actor', '[
											"http://joe.example.org",
											{
												"type": "Person",
												"name": "Sally"
											}
										]'							], # Set actor as multiple actors, JSON encoded, missing id for one actor
			[Activity::class, 'actor', '[
											"http://joe.example.org",
											{
												"type": "Person",
												"id": "http://",
												"name": "Sally"
											}
										]'							], # Set actor as multiple actors, JSON encoded, invalid id
			[Activity::class, 'actor', '[
											"http://",
											{
												"type": "Person",
												"id": "http://joe.example.org",
												"name": "Sally"
											}
										]'							], # Set actor as multiple actors, JSON encoded, invalid indirect link
			[Image::class, 'attributedTo', ' [
												{
													"type": "Person"
												}
											]'							], # Set attributedTo with a missing attribute (Array)
			[Image::class, 'attributedTo', '
												{
													"name": "Sally"
												}
											'							], # Set attributedTo with a single malformed type
			[Image::class, 'attributedTo', '
												{
													"type": "Link",

												}
											'							], # Set attributedTo with a malformed Link
			[Image::class, 'attributedTo', ' [
												"http://sally.example.org",
												{
													"type": "Person",
												}
											]'							], # Set attributedTo with an array of mixed URL and persons (malformed)
			[Note::class, 'attachment', '[
											{
												"type": "Image",
												"content": "This is what he looks like.",
											}
										]'							], # Set attachment with a missing reference
			[Note::class, 'attachment', '[
											{
												"type": "Link",
												"content": "This is what he looks like.",
											}
										]'							], # Set attachment with a missing reference
			[ObjectType::class, 'id', '1'							], # Set a number as id   (should pass @todo type resolver)
			[ObjectType::class, 'id', []							], # Set an array as id
			[Place::class, 'accuracy', -10							], # Set accuracy with a negative int
			[Place::class, 'accuracy', -0.0000001					], # Set accuracy with a negative float
			[Place::class, 'accuracy', 'A0.0000001'					], # Set accuracy with a non numeric value
			[Place::class, 'accuracy', 100.000001					], # Set accuracy with a float value out of range
			[Place::class, 'altitude', 100							], # Set altitude with an int value
			[Place::class, 'altitude', '100.5'						], # Set altitude with a text value
			[Place::class, 'altitude', 'hello'						], # Set altitude with a text value
			[Place::class, 'altitude', []							], # Set altitude with an array
			[Place::class, 'anyOf', []								], # Set anyOf for an inappropriate type
			[Question::class, 'anyOf', []							], # Set anyOf with an array
			[Question::class, 'anyOf', '[
											{
												"type": "Note",
											},
											{
												"type": "Note",
												"name": "Option B"
											}
										]'							], # Set anyOf with malformed choices 
			[Question::class, 'anyOf', '[
											{
												"type": "Note",
												"name": "Option A"
											},
											{
												"name": "Option B"
											}
										]'							], # Set anyOf with malformed choices 
			[Question::class, 'anyOf', '{
												"type": "Note",
												"name": "Option A"
										}'							], # Set anyOf with malformed choices 
			[Question::class, 'anyOf', '[
											{
												"type": "Note",
												"name": "Option A"
											},
											{
												"type": "Note",
												"name": ["Option B"]
											}
										]'							], # Set anyOf with malformed choices	
		];
	}

	/**
	 * Check that all core objects have a correct type property.
	 * It checks that getter is working well too.
	 * 
	 * @dataProvider      getValidAttributesScenarios
	 */
	public function testValidAttributesScenarios($type, $attr, $value)
	{
		$object = new $type();
		$object->{$attr} = $value;
		$this->assertEquals($value, $object->{$attr});
	}
	
	/**
	 * @dataProvider      getExceptionScenarios
	 * @expectedException \Exception
	 */
	public function testExceptionScenarios($type, $attr, $value)
	{
		$object = new $type();
		$object->{$attr} = $value;
	}

	/**
	 * Validator validate() method MUST receive an object as third parameter
	 * 
	 * @expectedException \Exception
	 */
	public function testValidatorValidateContainer()
	{	
		Validator::validate('property', 'value', 'NotAnObject');
	}


	/**
	 * Validator add method MUST receive an object that implements
	 * \ActivityPub\Type\ValidatorInterface interface
	 * 
	 * @expectedException \Exception
	 */
	public function testValidatorAddNotValidCustomValidator()
	{
		Validator::add('custom', new class {
			public function validate($value) {
				return true;
			}
		});
	}
}
