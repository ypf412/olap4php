<?php
/**
 * olap4php
 * 
 * LICENSE
 * 
 * Licensed to SeeWind Design Corp. under one or more 
 * contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  SeeWind Design licenses 
 * this file to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at:
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 * @category   olap4php
 * @copyright  See NOTICE file
 * @license    http://www.apache.org/licenses/LICENSE-2.0   Apache License, Version 2
 */
namespace OLAP4PHP\Provider\XMLA\Metadata;

// Clasess used
use OLAP4PHP\Common\NamedList;
use OLAP4PHP\Provider\XMLA\XMLATreeOp;
use OLAP4PHP\Provider\XMLA\XMLAConnectionContext;
use OLAP4PHP\Provider\XMLA\XMLALevel;
use OLAP4PHP\Provider\XMLA\XMLAMetadataRequest;


/**
 * @brief XMLA Metadata Reader Implementation
 */
class XMLAMetadataReader implements IXMLAMetadataReader
{
   /// The metadata readears cube
   private $cube;

   /**
    * Constructor
    */
   public function __construct ( $cube )
   {
      $this->cube = $cube;
   }

   /**
    * Looks up a member by its unique name.
    *
    * @param string $memberUniqueName Unique name of member
    * @return XMLAMember, or null if not found
    * @throws OLAPException if error occurs
    */
   public function lookupMemberByUniqueName ( $memberUniqueName )
   {
      $list = new NamedList ( );
      $this->lookupMemberRelatives (
         array ( XMLATreeOp::getEnum ( XMLATreeOp::SELF ) ),
         $memberUniqueName,
         $list );

      switch ( $list->size ( ) )
      {
         case 0:
            return null;
         case 1:
            return $list->get ( 0 );
         default:
            throw new \InvalidArgumentException (
               "more than one member with unique name '".$memberUniqueName."'" );
      }
   }

   /**
    * Looks up a list of members by their unique name and writes the results
    * into a map.
    *
    * @param array $memberUniqueNames List of unique names of member
    *
    * @param array $memberMap Map to populate with members
    *
    * @throws OLAPException if error occurs
    */
   public function lookupMembersByUniqueName ( array $memberUniqueNames, array& $memberMap )
   {
      // Iterates through member names
      foreach ( $memberUniqueNames as $memberName )
      {
         // Only lookup if it is not in the map yet
         if ( !isset ( $memberMap [ $memberName ] ) )
         {
            $member = $this->lookupMemberByUniqueName ( $memberName );
            // Null members might mean calculated members
            if ( $member != null )
            {
               $memberMap [ $member->getUniqueName ( ) ] = $member;
            }
         }
      }
   }

   /**
    * Looks a member by its unique name and returns members related by
    * the specified tree-operations.
    *
    * @param array treeOps Collection of tree operations to travel relative to
    * given member in order to create list of members
    *
    * @param string memberUniqueName Unique name of member
    *
    * @param array IMember List to be populated with members related to the given
    * member, or empty set if the member is not found
    *
    * @throws OLAPException if error occurs
    */
   public function lookupMemberRelatives ( array $treeOps, $memberUniqueName, NamedList $list )
   {
      $context = XMLAConnectionContext::createAtGranule ( $this->cube, null, null, null );
      $treeOpMask = 0;

      foreach ( $treeOps as $treeOp )
      {
         $treeOpMask |= $treeOp->xmlaOrdinal ( );
      }

      $this->cube->getSchema()->getCatalog()->getMetaData()->getConnection ( )
         ->populateList (
            $list,
            $context,
            new XMLAMetadataRequest( XMLAMetadataRequest::MDSCHEMA_MEMBERS ),
            new XMLAMemberHandler ( ),
            array ( 'CATALOG_NAME' => $this->cube->getSchema()->getCatalog()->getName(),
                    'SCHEMA_NAME' => $this->cube->getSchema()->getName(),
                    'CUBE_NAME' => $this->cube->getName(),
                    'MEMBER_UNIQUE_NAME' => $memberUniqueName,
                    'TREE_OP' => $treeOpMask )
            );
   }

   /**
    * Looks up members of a given level.
    *
    * @param XMLALevel level Level
    *
    * @throws OLAPException if error occurs
    *
    * @return NamedList
    */
   public function getLevelMembers ( XMLALevel $level )
   {
      throw new \BadMethodCallException ( 'Not implemented yet' );
   }
}