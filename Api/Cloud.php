<?php

/*
 * This file is part of the XabbuhPandaClient package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\PandaClient\Api;

use Xabbuh\PandaClient\Model\Profile;
use Xabbuh\PandaClient\Transformer\TransformerRegistry;
use Xabbuh\PandaClient\Transformer\TransformerRegistryInterface;

/**
 * Object-oriented interface to easily access a Panda cloud.
 * 
 * The implementation provides methods for accessing all endpoints of the Panda
 * encoding REST webservice. Each method is mapped to a corresponding HTTP
 * request.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class Cloud implements CloudInterface
{
    /**
     * The client which is used to perform the requests to the REST api
     * 
     * @var RestClientInterface
     */
    private $restClient;

    /**
     * @var TransformerRegistryInterface
     */
    private $transformers;
    
    /**
     * Constructs the Panda API instance on a given REST client.
     * 
     * @param RestClientInterface          $restClient   The client for REST requests
     * @param TransformerRegistryInterface $transformers
     */
    public function __construct(
        RestClientInterface $restClient,
        TransformerRegistryInterface $transformers
    ) {
        $this->restClient = $restClient;
        $this->transformers = $transformers;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getRestClient()
    {
        return $this->restClient;
    }

    /**
     * {@inheritDoc}
     */
    public function getVideos()
    {
        $response = $this->restClient->get('/videos.json');
        $transformer = $this->transformers->getVideoTransformer();
        return $transformer->fromJSONCollection($response);
    }

    /**
     * {@inheritDoc}
     */
    public function getVideosForPagination($page = 1, $per_page = 100)
    {
        $response = $this->restClient->get(
            '/videos.json',
            array(
                'include_root' => true,
                'page' => $page,
                'per_page' => $per_page
            )
        );
        $transformer = $this->transformers->getVideoTransformer();
        $result = json_decode($response);
        foreach ($result->videos as $index => $video) {
            $result->videos[$index] = $transformer->fromObject($video);
        }
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getVideo($videoId)
    {
        $response = $this->restClient->get('/videos/'.$videoId.'.json');
        $transformer = $this->transformers->getVideoTransformer();
        return $transformer->fromJSON($response);
    }

    /**
     * {@inheritDoc}
     */
    public function getVideoMetadata($videoId)
    {
        $response = $this->restClient->get('/videos/'.$videoId.'/metadata.json');
        return get_object_vars(json_decode($response));
    }

    /**
     * {@inheritDoc}
     */
    public function deleteVideo($videoId)
    {
        return $this->restClient->delete('/videos/'.$videoId.'.json');
    }

    /**
     * {@inheritDoc}
     */
    public function encodeVideoByUrl($url)
    {
        $response = $this->restClient->post('/videos.json', array('source_url' => $url));
        $transformer = $this->transformers->getVideoTransformer();
        return $transformer->fromJSON($response);
    }

    /**
     * {@inheritDoc}
     */
    public function encodeVideoFile($localPath)
    {
        $response = $this->restClient->post('/videos.json', array('file' => '@'.$localPath));
        $transformer = $this->transformers->getVideoTransformer();
        return $transformer->fromJSON($response);
    }

    /**
     * {@inheritDoc}
     */
    public function registerUpload(
        $filename,
        $fileSize,
        array $profiles = null,
        $useAllProfiles = false
    ) {
        if (!is_null($profiles)) {
            $options = array(
                'file_name' => $filename,
                'file_size' => $fileSize,
                'profiles' => implode(',', $profiles)
            );
        } else {
            $options = array(
                'file_name' => $filename,
                'file_size' => $fileSize,
                'use_all_profiles' => $useAllProfiles
            );
        }
        return json_decode($this->restClient->post('/videos/upload.json', $options));
    }

    /**
     * {@inheritDoc}
     */
    public function getEncodings(array $filter = array())
    {
        $response = $this->restClient->get('/encodings.json', $filter);
        $transformer = $this->transformers->getEncodingTransformer();
        return $transformer->fromJSONCollection($response);
    }

    /**
     * {@inheritDoc}
     */
    public function getEncodingsWithStatus($status, array $filter = array())
    {
        $filter['status'] = $status;
        return $this->getEncodings($filter);
    }

    /**
     * {@inheritDoc}
     */
    public function getEncodingsForProfile($profileId, array $filter = array())
    {
        $filter['profile_id'] = $profileId;
        return $this->getEncodings($filter);
    }

    /**
     * {@inheritDoc}
     */
    public function getEncodingsForProfileByName($profileName, array $filter = array())
    {
        $filter['profile_name'] = $profileName;
        return $this->getEncodings($filter);
    }

    /**
     * {@inheritDoc}
     */
    public function getEncodingsForVideo($videoId, array $filter = array())
    {
        $filter['video_id'] = $videoId;
        return $this->getEncodings($filter);
    }

    /**
     * {@inheritDoc}
     */
    public function getEncoding($encodingId)
    {
        $response = $this->restClient->get('/encodings/'.$encodingId.'.json');
        $transformer = $this->transformers->getEncodingTransformer();
        return $transformer->fromJSON($response);
    }

    /**
     * {@inheritDoc}
     */
    public function createEncoding($videoId, $profileId)
    {
        $response = $this->restClient->post(
            '/encodings.json',
            array('video_id' => $videoId, 'profile_id' => $profileId,)
        );
        $transformer = $this->transformers->getEncodingTransformer();
        return $transformer->fromJSON($response);
    }

    /**
     * {@inheritDoc}
     */
    public function createEncodingWithProfileName($videoId, $profileName)
    {
        $response = $this->restClient->post(
            '/encodings.json',
            array('video_id' => $videoId, 'profile_name' => $profileName,)
        );
        $transformer = $this->transformers->getEncodingTransformer();
        return $transformer->fromJSON($response);
    }

    /**
     * {@inheritDoc}
     */
    public function cancelEncoding($encodingId)
    {
        return $this->restClient->post('/encodings/'.$encodingId.'/cancel.json');
    }

    /**
     * {@inheritDoc}
     */
    public function retryEncoding($encodingId)
    {
        return $this->restClient->post('/encodings/'.$encodingId.'/retry.json');
    }

    /**
     * {@inheritDoc}
     */
    public function deleteEncoding($encodingId)
    {
        return $this->restClient->delete('/encodings/'.$encodingId.'.json');
    }

    /**
     * {@inheritDoc}
     */
    public function getProfiles()
    {
        $response = $this->restClient->get('/profiles.json');
        $transformer = $this->transformers->getProfileTransformer();
        return $transformer->fromJSONCollection($response);
    }

    /**
     * {@inheritDoc}
     */
    public function getProfile($profileId)
    {
        $response = $this->restClient->get('/profiles/'.$profileId.'.json');
        $transformer = $this->transformers->getProfileTransformer();
        return $transformer->fromJSON($response);
    }

    /**
     * {@inheritDoc}
     */
    public function addProfile(array $data)
    {
        $response = $this->restClient->post('/profiles.json', $data);
        $transformer = $this->transformers->getProfileTransformer();
        return $transformer->fromJSON($response);
    }

    /**
     * {@inheritDoc}
     */
    public function addProfileFromPreset($presetName)
    {
        $response = $this->restClient->post(
            '/profiles.json',
            array('preset_name' => $presetName)
        );
        $transformer = $this->transformers->getProfileTransformer();
        return $transformer->fromJSON($response);
    }

    /**
     * {@inheritDoc}
     */
    public function setProfile(Profile $profile)
    {
        $transformer = $this->transformers->getProfileTransformer();
        $response = $this->restClient->put(
            '/profiles/'.$profile->getId().'.json',
            $transformer->toRequestParams($profile)->all()
        );
        return $transformer->fromJSON($response);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteProfile(Profile $profile)
    {
        return $this->restClient->delete('/profiles/'.$profile->getId().'.json');
    }

    /**
     * {@inheritDoc}
     */
    public function getCloud($cloudId = null)
    {
        if (null === $cloudId) {
            $cloudId = $this->restClient->getCloudId();
        }

        $response = $this->restClient->get('/clouds/'.$cloudId.'.json');
        $transformer = $this->transformers->getCloudTransformer();
        return $transformer->fromJSON($response);
    }

    /**
     * {@inheritDoc}
     */
    public function setCloud(array $data, $cloudId = null)
    {
        if (null === $cloudId) {
            $cloudId = $this->restClient->getCloudId();
        }

        $response = $this->restClient->put('/clouds/'.$cloudId.'.json', $data);
        $transformer = $this->transformers->getCloudTransformer();
        return $transformer->fromJSON($response);
    }

    /**
     * {@inheritDoc}
     */
    public function getNotifications()
    {
        $response = $this->restClient->get('/notifications.json');
        $transformer = $this->transformers->getNotificationsTransformer();
        return $transformer->fromJSON($response);
    }

    /**
     * {@inheritDoc}
     */
    public function setNotifications(array $notifications)
    {
        $response = $this->restClient->put('/notifications.json', $notifications);
        $transformer = $this->transformers->getNotificationsTransformer();
        return $transformer->fromJSON($response);
    }
}
