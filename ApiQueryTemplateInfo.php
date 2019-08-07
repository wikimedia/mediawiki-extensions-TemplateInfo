<?php
/**
 * Adds the 'templateinfo' action to the MediaWiki API.
 *
 * @author Yaron Koren
 */

/**
 * @ingroup API
 */
class ApiQueryTemplateInfo extends ApiQueryBase {

	public function __construct( $query, $moduleName ) {
		parent :: __construct( $query, $moduleName, 'tli' );
	}

	public function execute() {
		$params = $this->extractRequestParams();
		$titles = $this->getPageSet()->getGoodTitles();
		if (count($titles) == 0)
			return;

		$this->addTables( 'page_props' );
		$this->addFields( array( 'pp_page', 'pp_value' ) );
		$this->addWhere( array(
			'pp_page' => array_keys( $titles ),
			'pp_propname' => 'templateinfo'
		) );
		if ( !is_null( $params['continue'] ) )
		{
			$fromid = intval( $params['continue'] );
			$this->addWhere( "pp_page >= $fromid" );
		}
		$this->addOption( 'ORDER BY', 'pp_page' );

		$res = $this->select(__METHOD__);
		while ( $row = $this->getDB()->fetchObject( $res ) ) {
			$vals = array( );
			$template_info = $row->pp_value;
			// determine whether this is actual XML or an error
			// message by checking whether the first character
			// is '<' - this is an interim solution until there's
			// a better storage format in place
			if (substr($template_info, 0, 1) == '<') {
				if ( defined( 'ApiResult::META_CONTENT' ) ) {
					ApiResult::setContentValue( $vals, 'value', $row->pp_value );
				} else {
					ApiResult::setContent( $vals, $row->pp_value );
				}
			} else {
				// add error message as an "error=" attribute
				$vals['error'] = $row->pp_value;
			}
			$fit = $this->addPageSubItems( $row->pp_page, $vals );
			if( !$fit ) {
				$this->setContinueEnumParameter( 'continue', $row->pp_page );
				break;
			}
		}
	}
	
	public function getAllowedParams() {
		return array (
			'continue' => array(
				ApiBase::PARAM_HELP_MSG => 'api-help-param-continue',
			),
		);
	}

	protected function getExamplesMessages() {
		return array (
			'action=query&prop=templateinfo&titles=Template:Foo|Template:Bar'
				=> 'apihelp-query+templateinfo-example-titles',
		);
	}
}
