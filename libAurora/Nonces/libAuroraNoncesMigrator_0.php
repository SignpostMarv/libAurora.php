<?php
//!	@file libAurora/Nonces/libAuroraNoncesMigrator_0.php
//!	@author SignpostMarv
//!	@brief Nonces migrator


namespace libAurora\Nonces{

	use Aurora\DataManager\Migration\Migrator;
	use Aurora\DataManager\Migration\Migrator\Schema;

	use Aurora\Framework\IDataConnector;

	use Aurora\Framework\ColumnType;
	use Aurora\Framework\ColumnDefinition;
	use Aurora\Framework\ColumnDefinition\Iterator as ColDefs;

	use Aurora\Framework\IndexType;
	use Aurora\Framework\IndexDefinition;
	use Aurora\Framework\IndexDefinition\Iterator as IndexDefs;


	class Migrator_0 extends Migrator{


		const MigrationName = 'LibAurora_Nonces';


		const BreakingChanges = 'libaurora_nonces';


		protected function __construct(){
			parent::__construct(); // argumenut validation

			#region libaurora_nonces

			$this->schema[] = new Schema(
				'libaurora_nonces',
				new ColDefs(
					new ColumnDefinition('nonce', array(
						'Type'           => ColumnType::UUID
					)),
					new ColumnDefinition('expiry', array(
						'Type'           => ColumnType::Integer,
						'Size'           => 11,
					))
				),
				new IndexDefs(
					new IndexDefinition(array('nonce'), IndexType::Primary)
				)
			);

			#endregion

		}


		protected function DoCreateDefaults(IDataConnector $genericData){
			$this->EnsureAllTablesInSchemaExist($genericData);
		}


		protected function DoValidate(IDataConnector $genericData){
			return $this->TestThatAllTablesValidate($genericData);
		}


		protected function DoMigrate(IDataConnector $genericData){
			$this->DoCreateDefaults($genericData);
		}


		protected function DoPrepareRestorePoint(IDataConnector $genericData){
			$this->CopyAllTablesToTempVersions($genericData);
		}


		public function FinishedMigration(IDataConnector $genericData){

		}
	}
}
?>