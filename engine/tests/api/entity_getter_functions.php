<?php

/**
 * Elgg Test Entity Getter Functions
 * @package Elgg
 * @subpackage Test
 * @author Curverider Ltd
 * @link http://elgg.org/
 */
class ElggCoreEntityGetterFunctionsTest extends ElggCoreUnitTest {
	/**
	 * Called before each test object.
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Called after each test method.
	 */
	public function setUp() {
		elgg_set_ignore_access(TRUE);
		$this->entities = array();
		$this->subtypes = array(
			'object' => array(),
			'user' => array(),
			'group' => array(),
			//'site'	=> array()
		);

		// sites are a bit wonky.  Don't use them just now.
		$this->types = array('object', 'user', 'group');

		// create some fun objects to play with.
		// 5 with random subtypes
		for ($i=0; $i<5; $i++) {
			$subtype = "test_object_subtype_" . rand();
			$e = new ElggObject();
			$e->subtype = $subtype;
			$e->save();
			$this->entities[] = $e;
			$this->subtypes['object'][] = $subtype;
		}

		// and users
		for ($i=0; $i<5; $i++) {
			$subtype = "test_user_subtype_" . rand();
			$e = new ElggUser();
			$e->username = "test_user_" . rand();
			$e->subtype = $subtype;
			$e->save();
			$this->entities[] = $e;
			$this->subtypes['user'][] = $subtype;
		}

		// and groups
		for ($i=0; $i<5; $i++) {
			$subtype = "test_group_subtype_" . rand();
			$e = new ElggGroup();
			$e->subtype = $subtype;
			$e->save();
			$this->entities[] = $e;
			$this->subtypes['group'][] = $subtype;
		}
	}

	/**
	 * Called after each test method.
	 */
	public function tearDown() {
		//$this->swallowErrors();
		foreach ($this->entities as $e) {
			$e->delete();
		}
	}

	/**
	 * Called after each test object.
	 */
	public function __destruct() {
		parent::__destruct();
	}


	/*************************************************
	 * Helpers for getting random types and subtypes *
	 *************************************************/

	/**
	 * Get a random valid subtype
	 *
	 * @param int $num
	 * @return array
	 */
	public function getRandomValidTypes($num = 1) {
		$r = array();

		for ($i=1; $i<=$num; $i++) {
			do {
				$t = $this->types[array_rand($this->types)];
			} while (in_array($t, $r) && count($r) < count($this->types));

			$r[] = $t;
		}

		shuffle($r);
		return $r;
	}


	/**
	 * Get a random valid subtype (that we just created)
	 *
	 * @param array $type Type of objects to return valid subtypes for.
	 * @param int $num of subtypes.
	 *
	 * @return array
	 */
	public function getRandomValidSubtypes(array $types, $num = 1) {
		$r = array();

		for ($i=1; $i<=$num; $i++) {
			do {
				// make sure at least one subtype of each type is returned.
				if ($i-1 < count($types)) {
					$type = $types[$i-1];
				} else {
					$type = $types[array_rand($types)];
				}

				$k = array_rand($this->subtypes[$type]);
				$t = $this->subtypes[$type][$k];
			} while (in_array($t, $r));

			$r[] = $t;
		}

		shuffle($r);
		return $r;
	}

	/**
	 * Return an array of invalid strings for type or subtypes.
	 *
	 * @param int $num
	 * @return arr
	 */
	public function getRandomInvalids($num = 1) {
		$r = array();

		for ($i=1; $i<=$num; $i++) {
			$r[] = 'random_invalid_' . rand();
		}

		return $r;
	}

	/**
	 *
	 * @param unknown_type $num
	 * @return unknown_type
	 */
	public function getRandomMixedTypes($num = 2) {
		$have_valid = $have_invalid = false;
		$r = array();

		// need at least one of each type.
		$valid_n = rand(1, $num-1);
		$r = array_merge($r, $this->getRandomValidTypes($valid_n));
		$r = array_merge($r, $this->getRandomInvalids($num - $valid_n));

		shuffle($r);
		return $r;
	}

	/**
	 * Get random mix of valid and invalid subtypes for types given.
	 *
	 * @param array $types
	 * @param unknown_type $num
	 * @return unknown_type
	 */
	public function getRandomMixedSubtypes(array $types, $num = 2) {
		$types_c = count($types);
		$r = array();

		// this can be more efficient but I'm very sleepy...

		// want at least one of valid and invalid of each type sent.
		for ($i=0; $i < $types_c && $num > 0; $i++) {
			// make sure we have a valid and invalid for each type
			if (true) {
				$type = $types[$i];
				$r = array_merge($r, $this->getRandomValidSubtypes(array($type), 1));
				$r = array_merge($r, $this->getRandomInvalids(1));

				$num -= 2;
			}
		}

		if ($num > 0) {
			$valid_n = rand(1, $num);
			$r = array_merge($r, $this->getRandomValidSubtypes($types, $valid_n));
			$r = array_merge($r, $this->getRandomInvalids($num - $valid_n));
		}

		//shuffle($r);
		return $r;
	}


	/***********************************
	 * TYPE TESTS
	 ***********************************
	 * check for getting a valid type in all ways we can.
	 * note that these aren't wonderful tests as there will be
	 * existing entities so we can't test against the ones we just created.
	 * So these just test that some are returned and match the type(s) requested.
	 * It could definitely be the case that the first 10 entities retrieved are all
	 * objects.  Maybe best to limit to 4 and group by type.
	 */
	public function testElggAPIGettersValidTypeUsingType() {
		$type_arr = $this->getRandomValidTypes();
		$type = $type_arr[0];
		$options = array(
			'type' => $type,
			'group_by' => 'e.type'
			);

			$es = elgg_get_entities($options);
			$this->assertIsA($es, 'array');

			// should only ever return one object because of group by
			$this->assertIdentical(count($es), 1);
			foreach ($es as $e) {
				$this->assertTrue(in_array($e->getType(), $type_arr));
			}
	}

	public function testElggAPIGettersValidTypeUsingTypesAsString() {
		$type_arr = $this->getRandomValidTypes();
		$type = $type_arr[0];
		$options = array(
			'types' => $type,
			'group_by' => 'e.type'
			);

			$es = elgg_get_entities($options);
			$this->assertIsA($es, 'array');

			// should only ever return one object because of group by
			$this->assertIdentical(count($es), 1);
			foreach ($es as $e) {
				$this->assertTrue(in_array($e->getType(), $type_arr));
			}
	}

	public function testElggAPIGettersValidTypeUsingTypesAsArray() {
		$type_arr = $this->getRandomValidTypes();
		$type = $type_arr[0];
		$options = array(
			'types' => $type_arr,
			'group_by' => 'e.type'
			);

			$es = elgg_get_entities($options);
			$this->assertIsA($es, 'array');

			// should only ever return one object because of group by
			$this->assertIdentical(count($es), 1);
			foreach ($es as $e) {
				$this->assertTrue(in_array($e->getType(), $type_arr));
			}
	}

	public function testElggAPIGettersValidTypeUsingTypesAsArrayPlural() {
		$num = 2;
		$types = $this->getRandomValidTypes($num);
		$options = array(
			'types' => $types,
			'group_by' => 'e.type'
			);

			$es = elgg_get_entities($options);
			$this->assertIsA($es, 'array');

			// one of object and one of group
			$this->assertIdentical(count($es), $num);

			foreach ($es as $e) {
				$this->assertTrue(in_array($e->getType(), $types));
			}
	}



	/*
	 * Test mixed valid and invalid types.
	 */


	public function testElggAPIGettersValidAndInvalidTypes() {
		//@todo replace this with $this->getRandomMixedTypes().
		$t = $this->getRandomValidTypes();
		$valid = $t[0];

		$t = $this->getRandomInvalids();
		$invalid = $t[0];
		$options = array(
			'types' => array($invalid, $valid),
			'group_by' => 'e.type'
			);

			$es = elgg_get_entities($options);
			$this->assertIsA($es, 'array');

			// should only ever return one object because of group by
			$this->assertIdentical(count($es), 1);
			$this->assertIdentical($es[0]->getType(), $valid);
	}

	public function testElggAPIGettersValidAndInvalidTypesPlural() {
		$valid_num = 2;
		$invalid_num = 3;
		$valid = $this->getRandomValidTypes($valid_num);
		$invalid = $this->getRandomInvalids($invalid_num);

		$types = array();
		foreach ($valid as $t) {
			$types[] = $t;
		}

		foreach ($invalid as $t) {
			$types[] = $t;
		}

		shuffle($types);
		$options = array(
			'types' => $types,
			'group_by' => 'e.type'
			);

			$es = elgg_get_entities($options);
			$this->assertIsA($es, 'array');

			// should only ever return one object because of group by
			$this->assertIdentical(count($es), $valid_num);
			foreach ($es as $e) {
				$this->assertTrue(in_array($e->getType(), $valid));
			}
	}



	/**************************************
	 * SUBTYPE TESTS
	 **************************************
	 *
	 * Here we can use the subtypes we created to test more finely.
	 * Subtypes are bound to types, so we must pass a type.
	 * This is where the fun logic starts.
	 */

	public function testElggAPIGettersValidSubtypeUsingSubtypeSingularType() {
		$types = $this->getRandomValidTypes();
		$subtypes = $this->getRandomValidSubtypes($types);
		$subtype = $subtypes[0];

		$options = array(
			'types' => $types,
			'subtype' => $subtype
		);

		$es = elgg_get_entities($options);
		$this->assertIsA($es, 'array');

		$this->assertIdentical(count($es), 1);
		foreach ($es as $e) {
			$this->assertTrue(in_array($e->getType(), $types));
			$this->assertTrue(in_array($e->getSubtype(), $subtypes));
		}
	}

	public function testElggAPIGettersValidSubtypeUsingSubtypesAsStringSingularType() {
		$types = $this->getRandomValidTypes();
		$subtypes = $this->getRandomValidSubtypes($types);
		$subtype = $subtypes[0];

		$options = array(
			'types' => $types,
			'subtypes' => $subtype
		);

		$es = elgg_get_entities($options);
		$this->assertIsA($es, 'array');

		$this->assertIdentical(count($es), 1);
		foreach ($es as $e) {
			$this->assertTrue(in_array($e->getType(), $types));
			$this->assertTrue(in_array($e->getSubtype(), $subtypes));
		}
	}

	public function testElggAPIGettersValidSubtypeUsingSubtypesAsArraySingularType() {
		$types = $this->getRandomValidTypes();
		$subtypes = $this->getRandomValidSubtypes($types);

		$options = array(
			'types' => $types,
			'subtypes' => $subtypes
		);

		$es = elgg_get_entities($options);
		$this->assertIsA($es, 'array');

		$this->assertIdentical(count($es), 1);
		foreach ($es as $e) {
			$this->assertTrue(in_array($e->getType(), $types));
			$this->assertTrue(in_array($e->getSubtype(), $subtypes));
		}
	}

	public function testElggAPIGettersValidSubtypeUsingPluralSubtypesSingularType() {
		$subtype_num = 2;
		$types = $this->getRandomValidTypes();
		$subtypes = $this->getRandomValidSubtypes($types, $subtype_num);

		$options = array(
			'types' => $types,
			'subtypes' => $subtypes
		);

		$es = elgg_get_entities($options);
		$this->assertIsA($es, 'array');

		$this->assertIdentical(count($es), $subtype_num);
		foreach ($es as $e) {
			$this->assertTrue(in_array($e->getType(), $types));
			$this->assertTrue(in_array($e->getSubtype(), $subtypes));
		}
	}


	/*
	Because we're looking for type OR subtype (sorta)
	it's possible that we've pulled in entities that aren't
	of the subtype we've requested.
	THIS COMBINATION MAKES LITTLE SENSE.
	There is no mechanism in elgg to retrieve a subtype without a type, so
	this combo gets trimmed down to only including subtypes that are valid to
	each particular type.
	FOR THE LOVE OF ALL GOOD PLEASE JUST USE TYPE_SUBTYPE_PAIRS!
	 */
	public function testElggAPIGettersValidSubtypeUsingPluralSubtypesPluralTypes() {
		$type_num = 2;
		$subtype_num = 2;
		$types = $this->getRandomValidTypes($type_num);
		$subtypes = $this->getRandomValidSubtypes($types, $subtype_num);

		$options = array(
			'types' => $types,
			'subtypes' => $subtypes
		);

		$es = elgg_get_entities($options);
		$this->assertIsA($es, 'array');

		// this will unset all invalid subtypes for each type that that only
		// one entity exists of each.
		$this->assertIdentical(count($es), $subtype_num);
		foreach ($es as $e) {
			// entities must at least be in the type.
			$this->assertTrue(in_array($e->getType(), $types));

			// test that this is a valid subtype for the entity type.
			$this->assertTrue(in_array($e->getSubtype(), $this->subtypes[$e->getType()]));
		}
	}

	/*
	 * This combination will remove all invalid subtypes for this type.
	 */
	public function testElggAPIGettersValidSubtypeUsingPluralMixedSubtypesSingleType() {
		$type_num = 1;
		$subtype_num = 2;
		$types = $this->getRandomValidTypes($type_num);


		//@todo replace this with $this->getRandomMixedSubtypes()
		// we want this to return an invalid subtype for the returned type.
		$subtype_types = $types;
		$i = 1;
		while ($i <= $subtype_num) {
			$type = $this->types[$i-1];

			if (!in_array($type, $subtype_types)) {
				$subtype_types[] = $type;
			}
			$i++;
		}

		$subtypes = $this->getRandomValidSubtypes($subtype_types, $type_num);

		$options = array(
			'types' => $types,
			'subtypes' => $subtypes
		);

		$es = elgg_get_entities($options);
		$this->assertIsA($es, 'array');

		// this will unset all invalid subtypes for each type that that only
		// one entity exists of each.
		$this->assertIdentical(count($es), $type_num);
		foreach ($es as $e) {
			// entities must at least be in the type.
			$this->assertTrue(in_array($e->getType(), $types));

			// test that this is a valid subtype for the entity type.
			$this->assertTrue(in_array($e->getSubtype(), $this->subtypes[$e->getType()]));
		}
	}


	/***************************
	 * TYPE_SUBTYPE_PAIRS
	 ***************************/


	public function testElggAPIGettersTSPValidTypeValidSubtype() {
		$type_num = 1;
		$subtype_num = 1;
		$types = $this->getRandomValidTypes($type_num);
		$subtypes = $this->getRandomValidSubtypes($types, $subtype_num);

		$pair = array($types[0] => $subtypes[0]);

		$options = array(
			'type_subtype_pairs' => $pair
		);

		$es = elgg_get_entities($options);
		$this->assertIsA($es, 'array');

		$this->assertIdentical(count($es), $type_num);
		foreach ($es as $e) {
			$this->assertTrue(in_array($e->getType(), $types));
			$this->assertTrue(in_array($e->getSubtype(), $subtypes));
		}
	}

	public function testElggAPIGettersTSPValidTypeValidPluralSubtype() {
		$type_num = 1;
		$subtype_num = 3;
		$types = $this->getRandomValidTypes($type_num);
		$subtypes = $this->getRandomValidSubtypes($types, $subtype_num);

		$pair = array($types[0] => $subtypes);

		$options = array(
			'type_subtype_pairs' => $pair
		);

		$es = elgg_get_entities($options);
		$this->assertIsA($es, 'array');

		$this->assertIdentical(count($es), $subtype_num);
		foreach ($es as $e) {
			$this->assertTrue(in_array($e->getType(), $types));
			$this->assertTrue(in_array($e->getSubtype(), $subtypes));
		}
	}

	public function testElggAPIGettersTSPValidTypeMixedPluralSubtype() {
		$type_num = 1;
		$valid_subtype_num = 2;
		$types = $this->getRandomValidTypes($type_num);
		$valid = $this->getRandomValidSubtypes($types, $valid_subtype_num);
		$invalid = $this->getRandomInvalids();

		$subtypes = array_merge($valid, $invalid);
		shuffle($subtypes);

		$pair = array($types[0] => $subtypes);

		$options = array(
			'type_subtype_pairs' => $pair
		);

		$es = elgg_get_entities($options);
		$this->assertIsA($es, 'array');

		$this->assertIdentical(count($es), $valid_subtype_num);
		foreach ($es as $e) {
			$this->assertTrue(in_array($e->getType(), $types));
			$this->assertTrue(in_array($e->getSubtype(), $valid));
		}
	}





	/****************************
	 * FALSE-RETURNING TESTS
	 ****************************
	 * The original bug corrected returned
	 * all entities when invalid subtypes were passed.
	 * Because there's a huge numer of combinations that
	 * return entities, I'm only writing tests for
	 * things that should return false.
	 *
	 * I'm leaving the above in case anyone is inspired to
	 * write out the rest of the possible combinations
	 */


	/*
	 * Test invalid types.
	 */
	public function testElggApiGettersInvalidTypeUsingType() {
		$type_arr = $this->getRandomInvalids();
		$type = $type_arr[0];

		$options = array(
			'type' => $type
		);

		$es = elgg_get_entities($options);
		$this->assertFalse($es);
	}


	public function testElggApiGettersInvalidTypeUsingTypesAsString() {
		$type_arr = $this->getRandomInvalids();
		$type = $type_arr[0];

		$options = array(
			'types' => $type
		);

		$es = elgg_get_entities($options);
		$this->assertFalse($es);
	}

	public function testElggApiGettersInvalidTypeUsingTypesAsArray() {
		$type_arr = $this->getRandomInvalids();

		$options = array(
			'types' => $type_arr
		);

		$es = elgg_get_entities($options);
		$this->assertFalse($es);
	}

	public function testElggApiGettersInvalidTypes() {
		$type_arr = $this->getRandomInvalids(2);

		$options = array(
			'types' => $type_arr
		);

		$es = elgg_get_entities($options);
		$this->assertFalse($es);
	}

	public function testElggApiGettersInvalidSubtypeValidType() {
		$type_num = 1;
		$subtype_num = 1;
		$types = $this->getRandomValidTypes($type_num);
		$subtypes = $this->getRandomInvalids($subtype_num);

		$options = array(
			'types' => $types,
			'subtypes' => $subtypes
		);

		$es = elgg_get_entities($options);
		$this->assertFalse($es);
	}

	public function testElggApiGettersInvalidSubtypeValidTypes() {
		$type_num = 2;
		$subtype_num = 1;
		$types = $this->getRandomValidTypes($type_num);
		$subtypes = $this->getRandomInvalids($subtype_num);

		$options = array(
			'types' => $types,
			'subtypes' => $subtypes
		);

		$es = elgg_get_entities($options);
		$this->assertFalse($es);
	}

	public function testElggApiGettersInvalidSubtypesValidType() {
		$type_num = 1;
		$subtype_num = 2;
		$types = $this->getRandomValidTypes($type_num);
		$subtypes = $this->getRandomInvalids($subtype_num);

		$options = array(
			'types' => $types,
			'subtypes' => $subtypes
		);

		$es = elgg_get_entities($options);
		$this->assertFalse($es);
	}

	public function testElggApiGettersInvalidSubtypesValidTypes() {
		$type_num = 2;
		$subtype_num = 2;
		$types = $this->getRandomValidTypes($type_num);
		$subtypes = $this->getRandomInvalids($subtype_num);

		$options = array(
			'types' => $types,
			'subtypes' => $subtypes
		);

		$es = elgg_get_entities($options);
		$this->assertFalse($es);
	}

	public function testElggApiGettersTSPInvalidType() {
		$type_num = 1;
		$types = $this->getRandomInvalids($type_num);

		$pair = array();

		foreach ($types as $type) {
			$pair[$type] = NULL;
		}

		$options = array(
			'type_subtype_pairs' => $pair
		);

		$es = elgg_get_entities($options);
		$this->assertFalse($es);
	}

	public function testElggApiGettersTSPInvalidTypes() {
		$type_num = 2;
		$types = $this->getRandomInvalids($type_num);

		$pair = array();
		foreach ($types as $type) {
			$pair[$type] = NULL;
		}

		$options = array(
			'type_subtype_pairs' => $pair
		);

		$es = elgg_get_entities($options);
		$this->assertFalse($es);
	}

	public function testElggApiGettersTSPValidTypeInvalidSubtype() {
		$type_num = 1;
		$subtype_num = 1;
		$types = $this->getRandomValidTypes($type_num);
		$subtypes = $this->getRandomInvalids($subtype_num);

		$pair = array($types[0] => $subtypes[0]);

		$options = array(
			'type_subtype_pairs' => $pair
		);

		$es = elgg_get_entities($options);
		$this->assertFalse($es);
	}

	public function testElggApiGettersTSPValidTypeInvalidSubtypes() {
		$type_num = 1;
		$subtype_num = 2;
		$types = $this->getRandomValidTypes($type_num);
		$subtypes = $this->getRandomInvalids($subtype_num);

		$pair = array($types[0] => array($subtypes[0], $subtypes[0]));

		$options = array(
			'type_subtype_pairs' => $pair
		);

		$es = elgg_get_entities($options);
		$this->assertFalse($es);
	}

	public function testElggApiGettersTSPValidTypesInvalidSubtypes() {
		$type_num = 2;
		$subtype_num = 2;
		$types = $this->getRandomValidTypes($type_num);
		$subtypes = $this->getRandomInvalids($subtype_num);

		$pair = array();
		foreach ($types as $type) {
			$pair[$type] = $subtypes;
		}

		$options = array(
			'type_subtype_pairs' => $pair
		);

		$es = elgg_get_entities($options);
		$this->assertFalse($es);
	}
}
