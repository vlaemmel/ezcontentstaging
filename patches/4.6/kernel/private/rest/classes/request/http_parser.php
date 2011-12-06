<?php
/**
 * File containing the ezpRestHttpRequestParser class
 *
 * @copyright Copyright (C) 1999-2011 eZ Systems AS. All rights reserved.
 * @license http://ez.no/eZPublish/Licenses/eZ-Business-Use-License-Agreement-eZ-BUL-Version-2.0 eZ Business Use License Agreement Version 2.0
 * @version 4.6.0
 * @package kernel
 */

/**
 * Custom request parser which creates instances of ezpRestRequest.
 *
 * The main difference is that GET and POST data is protected from potential
 * cookie pollution. And each category of variable has its own silo, to prevent
 * one from overwriting another.
 */
class ezpRestHttpRequestParser extends ezcMvcHttpRequestParser
{
    /**
     * @var ezpRestRequest
     */
    protected $request;

    /**
     * Overload createRequestObject() to make sure ezpRestRequest is created.
     *
     * @return ezpRestRequest
     */
    protected function createRequestObject()
    {
        return new ezpRestRequest();
    }

    /**
     * Overloads processVariables() to instead get ezpRest specific variables
     *
     * Note: ->variables is set with ezpRest specific variables instead of raw $_REQUEST.
     *
     * @return void
     */
    protected function processVariables()
    {
        $this->request->variables = $this->fillVariables();
        $this->request->contentVariables = $this->fillContentVariables();
        $this->request->get = $_GET;
        $this->request->post = $_POST;
    }

    /**
     * Overloads parent::processStandardHeaders() to also call processEncryption()
     *
     * @return void
     */
    protected function processStandardHeaders()
    {
        $this->processEncryption();
        parent::processStandardHeaders( );
        $this->processProtocolOverride();
    }

    /**
     * Sets the isEncrypted flag if HTTPS is on.
     *
     * @return void
     */
    protected function processEncryption()
    {
        if ( !empty( $_SERVER['HTTPS'] ) )
            $this->request->isEncrypted = true;
    }


    /**
     * Overloads processBody() to add support for body on POST and PUT
     * NB: this is called before processProtocolOverride(), so it uses the true protocol by default
     */
    protected function processBody()
    {
        $req = $this->request;

        if ( $req->protocol === 'http-put' ||  $req->protocol === 'http-post' )
        {
            $req->body = file_get_contents( "php://input" );
            if ( isset( $_SERVER['CONTENT_TYPE'] ) &&  strlen( $req->body ) > 0 )
            {
                switch( $_SERVER['CONTENT_TYPE'] )
                {
                    case 'application/json':
                    case 'json':
                        $variables = json_decode( $this->request->body, true );
                        if ( is_array( $variables ) )
                        {
                            $this->request->inputVariables = $variables;
                        }
                        else
                        {
                            /// @todo log warning
                            $this->request->inputVariables = null;
                        }
                        break;
                    case 'application/x-www-form-urlencoded':
                        if ( $req->protocol === 'http-put' )
                        {
                            $variables = array();
                            parse_str( $this->request->body, $variables );
                            $this->request->inputVariables = $variables;
                        }
                        else
                        {
                            $this->request->inputVariables = $_POST;
                        }
                        break;
                    default:
                        /// @todo log warning
                        $this->request->inputVariables = null;
                }
            }
        }
    }

    /**
     * Extract variables to be used internally from GET
     * @return array
     */
    protected function fillVariables()
    {
        $variables = array();
        $internalVariables = array( 'ResponseGroups' ); // Expected variables

        foreach( $internalVariables as $internalVariable )
        {
            if( isset( $_GET[$internalVariable] ) )
            {
                // Extract and organize variables as expected
                switch( $internalVariable )
                {
                    case 'ResponseGroups':
                        $variables[$internalVariable] = explode( ',', $_GET[$internalVariable] );
                        break;

                    default:
                        $variables[$internalVariable] = $_GET[$internalVariable];
                }

                unset( $_GET[$internalVariable] );
            }
            else
            {
                switch( $internalVariable )
                {
                    case 'ResponseGroups':
                        $variables[$internalVariable] = array();
                        break;

                    default:
                        $variables[$internalVariable] = null;
                }
            }
        }

        return $variables;
    }

    /**
     * Extract variables related to content from GET
     *
     * @return array
     */
    protected function fillContentVariables()
    {
        $contentVariables = array();
        $expectedVariables = array( 'Translation', 'OutputFormat' );

        foreach( $expectedVariables as $variable )
        {
            if( isset( $_GET[$variable] ) )
            {
                // Extract and organize variables as expected
                switch( $variable )
                {
                    case 'Translation': // @TODO => Make some control on the locale provided
                    default:
                        $contentVariables[$variable] = $_GET[$variable];
                }

                unset( $_GET[$variable] );
            }
            else
            {
                $contentVariables[$variable] = null;
            }
        }

        return $contentVariables;
    }

    /**
     * Adds support for using POST for PUT and DELETE for legacy browsers that does not support these.
     *
     * If a post param "_method" is set to either PUT or DELETE, then ->protocol is changed to that.
     * ( original protocol is kept on ->originalProtocol param  )
     * Post is used as this is only meant for forms in legacy browsers.
     */
    protected function processProtocolOverride()
    {
        $req = $this->request;
        $req->originalProtocol = $req->protocol;

        if ( $req->protocol === 'http-post' && isset( $req->post['_method'] ) )
        {
            $method = strtolower( $req->post['_method'] );
            if ( $method  === 'put' || $method === 'delete' )
                $req->protocol = "http-{$method}";

            unset( $req->post['_method'] );
        }
    }
}
