<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

namespace pocketmine\level\generator\biome;

use pocketmine\block\Block;
use pocketmine\level\ChunkManager;
use pocketmine\level\generator\normal\biome\MesaBiome;
use pocketmine\level\generator\normal\biome\BeachBiome;
use pocketmine\level\generator\normal\biome\SwampBiome;
use pocketmine\level\generator\normal\biome\DesertBiome;
use pocketmine\level\generator\normal\biome\ForestBiome;
use pocketmine\level\generator\normal\biome\IcePlainsBiome;
use pocketmine\level\generator\normal\biome\MountainsBiome;
use pocketmine\level\generator\normal\biome\OceanBiome;
use pocketmine\level\generator\normal\biome\PlainBiome;
use pocketmine\level\generator\normal\biome\RiverBiome;
use pocketmine\level\generator\normal\biome\SmallMountainsBiome;
use pocketmine\level\generator\normal\biome\TaigaBiome;
use pocketmine\level\generator\nether\biome\HellBiome;
use pocketmine\level\generator\populator\Populator;
use pocketmine\utils\Random;

use pocketmine\level\generator\normal\populator\Flower;

abstract class Biome {

	const OCEAN = 0;
	const PLAINS = 1;
	const DESERT = 2;
	const MOUNTAINS = 3;
	const FOREST = 4;
	const TAIGA = 5;
	const SWAMP = 6;
	const RIVER = 7;
	const HELL = 8;
	const END = 9;
	const FROZEN_OCEAN = 10;
	const FROZEN_RIVER = 11;
	const ICE_PLAINS = 12;
	const ICE_MOUNTAINS = 13;
	const MUSHROOM_ISLAND = 14;
	const MUSHROOM_ISLAND_SHORE = 15;
	const BEACH = 16;
	const DESERT_HILLS = 17;
	const FOREST_HILLS = 18;
	const TAIGA_HILLS = 19;
	const SMALL_MOUNTAINS = 20;
	const BIRCH_FOREST = 27;
	const BIRCH_FOREST_HILLS = 28;
	const ROOFED_FOREST = 29;
	const COLD_TAIGA = 30;
	const COLD_TAIGA_HILLS = 31;
	const MEGA_TAIGA = 32;
	const MEGA_TAIGA_HILLS = 33;
	const EXTREME_HILLS_PLUS = 34;
	const SAVANNA = 35;
	const SAVANNA_PLATEAU = 36;
	const MESA = 37;
	const MESA_PLATEAU_F = 38;
	const MESA_PLATEAU = 39;

	const VOID = 127;

	const MAX_BIOMES = 256;

	/** @var Biome[] */
	private static $biomes = [];
	protected $rainfall = 0.5;
	protected $temperature = 0.5;
	private $id;
	private $registered = false;
	/** @var Populator[] */
	private $populators = [];
	private $minElevation;
	private $maxElevation;
	private $groundCover = [];

	public static function init(){
		self::register(self::OCEAN, new OceanBiome());
		self::register(self::PLAINS, new PlainBiome());
		self::register(self::DESERT, new DesertBiome());
		self::register(self::MOUNTAINS, new MountainsBiome());
		self::register(self::FOREST, new ForestBiome());
		self::register(self::TAIGA, new TaigaBiome());
		self::register(self::SWAMP, new SwampBiome());
		self::register(self::RIVER, new RiverBiome());

		self::register(self::BEACH, new BeachBiome());
		self::register(self::MESA, new MesaBiome());

		self::register(self::ICE_PLAINS, new IcePlainsBiome());


		self::register(self::SMALL_MOUNTAINS, new SmallMountainsBiome());
		self::register(self::HELL, new HellBiome());

		self::register(self::BIRCH_FOREST, new ForestBiome(ForestBiome::TYPE_BIRCH));
	}

	protected static function register($id, Biome $biome){
		self::$biomes[(int) $id] = $biome;
		$biome->setId((int) $id);

		$flowerPopFound = false;

		foreach($biome->getPopulators() as $populator){
			if($populator instanceof Flower){
				$flowerPopFound = true;
				break;
			}
		}

		if($flowerPopFound === false){
			$flower = new Flower();
			$biome->addPopulator($flower);
		}
	}

	public function getPopulators(){
		return $this->populators;
	}

	public function addPopulator(Populator $populator){
		$this->populators[get_class($populator)] = $populator;
	}

	/**
	 * @param $id
	 *
	 * @return Biome
	 */
	public static function getBiome($id){
		return isset(self::$biomes[$id]) ? self::$biomes[$id] : self::$biomes[self::OCEAN];
	}

	public function clearPopulators(){
		$this->populators = [];
	}

	public function removePopulator($class){
		if(isset($this->populators[$class])){
			unset($this->populators[$class]);
		}
	}

	public function populateChunk(ChunkManager $level, $chunkX, $chunkZ, Random $random){
		foreach($this->populators as $populator){
			$populator->populate($level, $chunkX, $chunkZ, $random);
		}
	}

	public function getId(){
		return $this->id;
	}

	public function setId($id){
		if(!$this->registered){
			$this->registered = true;
			$this->id = $id;
		}
	}

	public abstract function getName();

	public function getMinElevation(){
		return $this->minElevation;
	}

	public function getMaxElevation(){
		return $this->maxElevation;
	}

	public function setElevation($min, $max){
		$this->minElevation = $min;
		$this->maxElevation = $max;
	}

	/**
	 * @return Block[]
	 */
	public function getGroundCover(){
		return $this->groundCover;
	}

	/**
	 * @param Block[] $covers
	 */
	public function setGroundCover(array $covers){
		$this->groundCover = $covers;
	}

	public function getTemperature(){
		return $this->temperature;
	}

	public function getRainfall(){
		return $this->rainfall;
	}
}