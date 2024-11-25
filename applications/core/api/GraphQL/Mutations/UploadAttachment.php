<?php
/**
 * @brief		GraphQL: Upload attachment mutation
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		22 May 2019
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL\Mutations;
use Exception;
use GraphQL\Type\Definition\UnionType;
use InvalidArgumentException;
use IPS\Api\GraphQL\SafeException;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\File;
use IPS\Helpers\Form\Editor;
use IPS\Image;
use IPS\Member;
use function defined;
use function in_array;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Upload attachment mutation for GraphQL API
 */
class UploadAttachment
{
	/*
	 * @brief 	Query description
	 */
	public static string $description = "Upload a file to be an attachment";
	
	/*
	 * Mutation arguments
	 */
	public function args(): array
	{
		return [
			'name'			=> TypeRegistry::nonNull( TypeRegistry::string() ),
			'contents'		=> TypeRegistry::nonNull( TypeRegistry::string() ),
			'postKey'		=> TypeRegistry::nonNull( TypeRegistry::string() ),
			'chunk'			=> TypeRegistry::int(),
			'totalChunks'	=> TypeRegistry::int(),
			'ref'			=> TypeRegistry::nonNull( TypeRegistry::string() ),
		];
	}
	
	/**
	 * Return the mutation return type
	 */
	public function type() : UnionType
	{
		return new UnionType([
			'name' => 'core_UploadAttachment',
			'types' => [
				\IPS\core\api\GraphQL\TypeRegistry::attachment(),
				\IPS\core\api\GraphQL\TypeRegistry::uploadProgress(),
			],
			'resolveType' => function ($obj) {
				if ( isset( $obj['ref'] ) )
				{
					return \IPS\core\api\GraphQL\TypeRegistry::uploadProgress();
				}
				else
				{
					return \IPS\core\api\GraphQL\TypeRegistry::attachment();
				} 
			}
		]);
	}
	
	/**
	 * Resolves this mutation
	 *
	 * @param mixed $val Value passed into this resolver
	 * @param array $args Arguments
	 * @param array $context Context values
	 * @param array $info
	 * @return	array
	 */
	public function resolve( mixed $val, array $args, array $context, array $info ) : array
	{
		$storageClass = File::getClass( 'core_Attachment' );
		$contents = base64_decode( $args['contents'] );
		
		/* Check allowed types */
		$ext = mb_substr( $args['name'], mb_strrpos( $args['name'], '.' ) + 1 );
		if( $allowedFileTypes = Editor::allowedFileExtensions() )
		{
			if( !in_array( mb_strtolower( $ext ), array_map( 'mb_strtolower', $allowedFileTypes ) ) )
			{
				throw new SafeException( 'FILE_TYPE_NOT_ALLOWED', '2C399/1_graphql', 403 );
			}
		}
		
		/* Chunking? */
		if ( $storageClass::$supportsChunking and isset( $args['chunk'] ) )
		{
			/* If this is the FIRST chunk, start the process */		
			if ( $args['chunk'] === 1 )
			{
				$ref = $storageClass->chunkInit( $args['name'] );
			}
			else
			{
				$ref = json_decode( $args['ref'] );
			}
			
			/* Process this chunk */
			$ref = $storageClass->chunkProcess( $ref, $contents, --$args['chunk'], TRUE );
			
			/* If this is the LAST chunk, finish the process */	
			if ( $args['chunk'] === $args['totalChunks'] )
			{
				$file = $storageClass->chunkFinish( $ref, 'core_Attachment' );
				
				/* If it's got an image extension, check it's actually a valid image */
				if ( in_array( $ext, Image::supportedExtensions() ) )
				{
					try
					{
						$file->getImageDimensions();
					}
					catch ( Exception $e )
					{
						throw new SafeException( 'NOT_VALID_IMAGE', '2C399/2_graphql', 403 );
					}
				}
			}
			else
			{
				// If we're continuing to the next chunk, return an UploadProgress object containing the ref
				$ref = json_encode( $ref );

				return array(
					'name' => $args['name'],
					'ref' => $args['ref']
				);
			}
		}
		else
		{
			/* If it's got an image extension, check it's actually a valid image */
			if ( in_array( $ext, Image::supportedExtensions() ) )
			{
				try
				{
					$image = Image::create( $contents );
				}
				catch ( InvalidArgumentException $e )
				{
					throw new SafeException( $e->getMessage(), '2C399/2_graphql', 403 );
				}
			}
			
			/* Create the file */
			try
			{
				$file = File::create( 'core_Attachment', $args['name'], $contents );
			}
			catch ( Exception $e )
			{
				throw new SafeException( 'FILE_CREATION_FAILED', '2C399/3_graphql', 403 );
			}
		}
		
		/* Make it into an attachment */		
		return $file->makeAttachment( $args['postKey'], Member::loggedIn() );
	}
}