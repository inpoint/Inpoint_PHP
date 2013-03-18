<?php

class Fingerprint
{
	public $room;
	public $position;
	public $mac;
	public $average;
	public $variance;
	public $count_;
	public $id;

	function __construct($room, $position, $mac, $average, $variance, $count, $id) {
		$this->room = $room;
		$this->position = $position;
		$this->mac = (string) $mac;
		$this->average = $average;
		$this->variance = $variance;
		$this->count_ = $count;
		$this->id = $id;
	}
	function get_room() {

		return $this->room;
	}
	function get_position() {

		return $this->position;
	}
	function get_mac() {

		return $this->mac;
	}
	function get_average() {

		return $this->average;
	}
	function get_variance() {

		return $this->variance;
	}
	function get_count() {

		return $this->count_;
	}
	function get_id() {

		return $this->id;
	}

}

class FprintRelations
{
	private $room;
	private $position;
	private $relations = array();

	function __construct($room, $position, $relations) {

		$this->room = $room;
		$this->position = $position;
		$this->relations = $relations;	

	}

	function get_room() {
	
		return $this->room;
	
	}

	function get_position() {

		return $this->position;

	}

	function get_relations() {
	
		return $this->relations;
	}
}


class FprintDifference
{
	private $room;
	private $position;
	private $difference;

	function __construct($room, $position, $difference) {

		$this->room = $room;
		$this->position = $position;
		$this->difference = $difference;

	}

	function get_room() {
	
		return $this->room;
	
	}

	function get_position() {

		return $this->position;

	}

	function get_difference() {

		return $this->difference;


	}


}


?>
