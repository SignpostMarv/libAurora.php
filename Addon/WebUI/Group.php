<?php
//!	@file libs/Aurora/Addon/WebUI/Group.php
//!	@brief Group-related WebUI code
//!	@author SignpostMarv


namespace Aurora\Addon\WebUI{

	use SeekableIterator;

	use Aurora\Addon\WebUI;
	use Aurora\Framework;

//!	Implementation of Aurora::Framework::GroupRecord
	class GroupRecord implements Framework\GroupRecord{

//!	We hide this behind a registry method.
/**
*	@param string $uuid group UUID
*	@param string $name group name
*	@param string $charter group charter
*	@param string $insignia asset ID for group insignia
*	@param string $founder user UUID for group founder
*	@param integer $membershipFee fee required to join the group
*	@param bool $openEnrollment TRUE if anyone can join, FALSE if they need to be invited.
*	@param bool $showInList TRUE if shown in search, FALSE otherwise
*	@param bool $allowPublish not too sure what this does, as I thought it was what $showInList was for.
*	@param bool $maturePublish TRUE if group is mature-rated, FALSE otherwise.
*	@param string $ownerRoleID UUID for owner role.
*/
		protected function __construct($uuid, $name, $charter, $insignia, $founder, $membershipFee, $openEnrollment, $showInList, $allowPublish, $maturePublish, $ownerRoleID){
			if(is_string($name) === true){
				$name = trim($name);
			}
			if(is_string($charter) === true){
				$charter = trim($charter);
			}
			if(is_string($membershipFee) === true && ctype_digit($membershipFee) === true){
				$membershipFee = (integer)$membershipFee;
			}
			if(is_string($openEnrollment) === true && ctype_digit($openEnrollment) === true){
				$openEnrollment = (bool)$openEnrollment;
			}
			if(is_string($showInList) === true && ctype_digit($showInList) === true){
				$showInList = (bool)$showInList;
			}
			if(is_string($allowPublish) === true && ctype_digit($allowPublish) === true){
				$allowPublish = (bool)$allowPublish;
			}
			if(is_string($maturePublish) === true && ctype_digit($maturePublish) === true){
				$maturePublish = (bool)$maturePublish;
			}

			if(is_string($uuid) === false){
				throw new InvalidArgumentException('Group ID should be a string.');
			}else if(preg_match(WebUI::regex_UUID, $uuid) !== 1){
				throw new InvalidArgumentException('Group ID should be a valid UUID.');
			}else if(is_string($name) === false){
				throw new InvalidArgumentException('Group name should be a string.');
			}else if($name === ''){
				throw new InvalidArgumentException('Group name should be a non-empty string.');
			}else if(is_string($charter) === false){
				throw new InvalidArgumentException('Group charter should be a string.');
			}else if(is_string($insignia) === false){
				throw new InvalidArgumentException('Group insignia should be a string.');
			}else if(preg_match(WebUI::regex_UUID, $insignia) !== 1){
				throw new InvalidArgumentException('Group insignia should be a valid UUID.');
			}else if(is_string($founder) === false){
				throw new InvalidArgumentException('Group founder should be a string.');
			}else if(preg_match(WebUI::regex_UUID, $founder) !== 1){
				throw new InvalidArgumentException('Group founder should be a valid UUID.');
			}else if(is_integer($membershipFee) === false){
				throw new InvalidArgumentException('Membership fee should be an integer.');
			}else if($membershipFee < 0){
				throw new InvalidArgumentException('Membership fee should be greater than or equal to zero.');
			}else if(is_bool($openEnrollment) === false){
				throw new InvalidArgumentException('Open Enrollment should be a boolean.');
			}else if(is_bool($showInList) === false){
				throw new InvalidArgumentException('Show in list flag should be a boolean.');
			}else if(is_bool($allowPublish) === false){
				throw new InvalidArgumentException('Allow publish flag should be a boolean.');
			}else if(is_bool($maturePublish) === false){
				throw new InvalidArgumentException('Mature publish flag should be a boolean.');
			}else if(is_string($ownerRoleID) === false){
				throw new InvalidArgumentException('Owner role ID should be a string.');
			}else if(preg_match(WebUI::regex_UUID, $ownerRoleID) !== 1){
				throw new InvalidArgumentException('Owner role ID should be a valid UUID.');
			}

			$this->GroupID        = $uuid;
			$this->GroupName      = $name;
			$this->Charter        = $charter;
			$this->GroupPicture   = $insignia;
			$this->FounderID      = $founder;
			$this->MembershipFee  = $membershipFee;
			$this->OpenEnrollment = $openEnrollment;
			$this->ShowInList     = $showInList;
			$this->AllowPublish   = $allowPublish;
			$this->MaturePublish  = $maturePublish;
			$this->OwnerRoleID    = $ownerRoleID;
		}

//!	We use a registry method since groups are uniquely identified by UUIDs
/**
*	@param string $uuid group UUID
*	@param mixed $name group name
*	@param mixed $charter group charter
*	@param mixed $insignia asset ID for group insignia
*	@param mixed $founder user UUID for group founder
*	@param mixed $membershipFee fee required to join the group
*	@param mixed $openEnrollment TRUE if anyone can join, FALSE if they need to be invited.
*	@param mixed $showInList TRUE if shown in search, FALSE otherwise
*	@param mixed $allowPublish not too sure what this does, as I thought it was what $showInList was for.
*	@param mixed $maturePublish TRUE if group is mature-rated, FALSE otherwise.
*	@param mixed $ownerRoleID UUID for owner role.
*	@return object Aurora::Addon::WebUI::GroupRecord
*/
		public static function r($uuid, $name=null, $charter=null, $insignia=null, $founder=null, $membershipFee=null, $openEnrollment=null, $showInList=null, $allowPublish=null, $maturePublish=null, $ownerRoleID=null){
			if(is_string($uuid) === false){
				throw new InvalidArgumentException('Group ID should be a string.');
			}else if(preg_match(WebUI::regex_UUID, $uuid) !== 1){
				throw new InvalidArgumentException('Group ID should be a valid UUID.');
			}
			$uuid = strtolower($uuid);

			static $registry = array();

			$create = (isset($registry[$uuid]) === false);

			if($create === true && isset($name, $charter, $insignia, $founder, $membershipFee, $openEnrollment, $showInList, $allowPublish, $maturePublish, $ownerRoleID) === false){
				throw new InvalidArgumentException('Cannot return a group instance, group was never set.');
			}else if($create === false){
				$group = $registry[$uuid];

				$create = (
					$group->GroupName()      != $name ||
					$group->Charter()        != $charter ||
					$group->GroupPicture()   != $insignia ||
					$group->FounderID()      != $founder ||
					$group->MembershipFee()  != $membershipFee ||
					$group->OpenEnrollment() != $openEnrollment ||
					$group->ShowInList()     != $showInList ||
					$group->AllowPublish()   != $allowPublish ||
					$group->MaturePublish()  != $MaturePublish ||
					$group->OwnerRoleID()    != $ownerRoleID
				);
			}

			if($create === true){
				$registry[$uuid] = new static($uuid, $name, $charter, $insignia, $founder, $membershipFee, $openEnrollment, $showInList, $allowPublish, $maturePublish, $ownerRoleID);
			}

			return $registry[$uuid];
		}


		protected $GroupID;
		public function GroupID(){
			return $this->GroupID;
		}


		protected $GroupName;
		public function GroupName(){
			return $this->GroupName;
		}


		protected $Charter;
		public function Charter(){
			return $this->Charter;
		}


		protected $GroupPicture;
		public function GroupPicture(){
			return $this->GroupPicture;
		}


		protected $FounderID;
		public function FounderID(){
			return $this->FounderID;
		}


		protected $MembershipFee;
		public function MembershipFee(){
			return $this->MembershipFee;
		}


		protected $OpenEnrollment;
		public function OpenEnrollment(){
			return $this->OpenEnrollment;
		}


		protected $ShowInList;
		public function ShowInList(){
			return $this->ShowInList;
		}


		protected $AllowPublish;
		public function AllowPublish(){
			return $this->AllowPublish;
		}


		protected $MaturePublish;
		public function MaturePublish(){
			return $this->MaturePublish;
		}


		protected $OwnerRoleID;
		public function OwnerRoleID(){
			return $this->OwnerRoleID;
		}
	}

//!	Groups iterator
	class GetGroupRecords extends WebUI\abstractSeekableFilterableIterator{

//!	Because we use a seekable iterator, we hide the constructor behind a registry method to avoid needlessly calling the end-point if we've rewound the iterator, or moved the cursor to an already populated position.
/**
*	@param object $WebUI instance of Aurora::Addon::WebUI We need to specify this in case we want to iterate past the original set of results.
*	@param integer $start initial cursor position
*	@param integer $total Total number of results possible with specified filters
*	@param array $sort optional array of field names for keys and booleans for values, indicating ASC and DESC sort orders for the specified fields.
*	@param array $boolFields optional array of field names for keys and booleans for values, indicating 1 and 0 for field values.
*	@param array $groups if specified, should be an array of instances of Aurora::Addon::WebUI::GroupRecord that were pre-fetched with a call to the API end-point.
*/
		protected function __construct(WebUI $WebUI, $start=0, $total=0, array $sort=null, array $boolFields=null, array $groups=null){
			parent::__construct($WebUI, $start, $total, $sort, $boolFields);
			if(isset($groups) === true){
				$i = $start;
				foreach($groups as $group){
					if($group instanceof GroupRecord){
						$this->data[$i++] = $group;
					}else{
						throw new InvalidArgumentException('Only instances of Aurora::Addon::WebUI::GroupRecord should be passed to Aurora::Addon::WebUI::GetGroupRecords::__construct()');
					}
				}
			}
		}

//!	To avoid slowdowns due to an excessive amount of curl calls, we populate Aurora::Addon::WebUI::GetGroupRecords::$data in batches of 10
/**
*	@return mixed either NULL or an instance of Aurora::Addon::WebUI::GroupRecord
*/
		public function current(){
			if($this->valid() === false){
				return null;
			}else if(isset($this->data[$this->key()]) === false){
				$start   = $this->key();
				$results = $this->WebUI->GetGroups($start, 10, $this->sort, $this->boolFields);
				foreach($results as $group){
					$this->data[$start++] = $group;
				}
			}
			return $this->data[$this->key()];
		}
	}
}
?>