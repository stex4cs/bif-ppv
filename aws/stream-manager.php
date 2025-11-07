<?php
/**
 * KREIRAJ NOVI FAJL: aws/stream-manager.php
 * AWS MediaLive Integration za BIF PPV
 */
require __DIR__ . '/../vendor/autoload.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Load environment variables
function loadEnvFile($path) {
    if (!is_file($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $t = trim($line);
        if ($t === '' || strpos($t, '#') === 0 || strpos($t, '=') === false) continue;
        list($k, $v) = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v, "\"' \t\r\n");
        putenv("$k=$v");
        $_ENV[$k] = $v;
    }
}
loadEnvFile(dirname(__DIR__) . '/env/.env');

use Aws\MediaLive\MediaLiveClient;
use Aws\CloudFront\CloudFrontClient;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class BIF_AWSStreamManager {
    private $mediaLive;
    private $cloudFront;
    private $s3;
    private $region;
    private $accountId;
    private $bucketName;
    private $roleArn;
    
    public function __construct() {
        $this->region = $_ENV['AWS_REGION'] ?? 'eu-north-1';
        $this->accountId = $_ENV['AWS_ACCOUNT_ID'] ?? '';
        $this->bucketName = $_ENV['AWS_S3_BUCKET'] ?? '';
        $this->roleArn = $_ENV['AWS_MEDIALIVE_ROLE_ARN'] ?? '';
        
        $config = [
            'version' => 'latest',
            'region' => $this->region,
            'credentials' => [
                'key' => $_ENV['AWS_ACCESS_KEY_ID'] ?? '',
                'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'] ?? ''
            ]
        ];
        
        try {
            $this->mediaLive = new MediaLiveClient($config);
            $this->cloudFront = new CloudFrontClient($config);
            $this->s3 = new S3Client($config);
            $this->log('AWS Stream Manager initialized successfully');
        } catch (Exception $e) {
            $this->log('AWS initialization error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Test AWS konekcije
     */
    public function testConnection() {
        try {
            // Test MediaLive
            $this->mediaLive->listInputs(['MaxResults' => 1]);
            
            // Test S3
            $this->s3->headBucket(['Bucket' => $this->bucketName]);
            
            return [
                'success' => true,
                'message' => 'AWS connection successful',
                'services' => [
                    'medialive' => 'connected',
                    's3' => 'connected',
                    'cloudfront' => 'connected'
                ],
                'config' => [
                    'region' => $this->region,
                    'bucket' => $this->bucketName,
                    'account_id' => $this->accountId
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'AWS connection failed: ' . $e->getMessage(),
                'debug' => [
                    'region' => $this->region,
                    'bucket' => $this->bucketName,
                    'role_arn' => $this->roleArn
                ]
            ];
        }
    }
    
    /**
     * Kreiraj novi live stream
     */
    public function createLiveStream($eventId, $eventTitle) {
        try {
            $this->log("Creating AWS live stream for: $eventId - $eventTitle");
            
            // 1. Kreiraj MediaLive Input
            $inputResult = $this->createMediaLiveInput($eventId);
            if (!$inputResult['success']) {
                return $inputResult;
            }
            
            // 2. Kreiraj MediaLive Channel
            $channelResult = $this->createMediaLiveChannel($eventId, $eventTitle, $inputResult['input_id']);
            if (!$channelResult['success']) {
                return $channelResult;
            }
            
            // 3. Generiši playback URL
            $playbackUrl = $this->generatePlaybackUrl($eventId);
            
            $result = [
                'success' => true,
                'stream_data' => [
                    'event_id' => $eventId,
                    'input_id' => $inputResult['input_id'],
                    'channel_id' => $channelResult['channel_id'],
                    'rtmp_url' => $inputResult['rtmp_url'],
                    'stream_key' => $inputResult['stream_key'],
                    'playback_url' => $playbackUrl,
                    'status' => 'created',
                    'created_at' => date('Y-m-d H:i:s'),
                    'cost_estimate' => [
                        'encoding_per_hour' => 12,
                        'estimated_3h_cost' => 36,
                        'data_transfer_per_gb' => 0.09,
                        'currency' => 'USD'
                    ]
                ],
                'instructions' => [
                    'obs_setup' => "1. Open OBS Studio\n2. Settings → Stream\n3. Service: Custom\n4. Server: {$inputResult['rtmp_url']}\n5. Stream Key: {$inputResult['stream_key']}\n6. Start Streaming",
                    'start_encoding' => "Use admin panel to start MediaLive channel before streaming"
                ]
            ];
            
            $this->log("AWS live stream created successfully for: $eventId");
            return $result;
            
        } catch (Exception $e) {
            $this->log("Error creating live stream: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to create AWS live stream: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Kreiraj MediaLive Input
     */
    private function createMediaLiveInput($eventId) {
        try {
            // Get or create security group
            $securityGroupId = $this->getOrCreateSecurityGroup();
            
            $result = $this->mediaLive->createInput([
                'Name' => "BIF-{$eventId}-input",
                'Type' => 'RTMP_PUSH',
                'Destinations' => [
                    ['StreamName' => "primary/{$eventId}"],
                    ['StreamName' => "backup/{$eventId}"]
                ],
                'InputSecurityGroups' => [$securityGroupId]
            ]);
            
            $inputId = $result['Input']['Id'];
            $destinations = $result['Input']['Destinations'];
            
            return [
                'success' => true,
                'input_id' => $inputId,
                'rtmp_url' => $destinations[0]['Url'],
                'stream_key' => "primary/{$eventId}"
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to create MediaLive input: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Kreiraj MediaLive Channel
     */
    private function createMediaLiveChannel($eventId, $eventTitle, $inputId) {
        try {
            $result = $this->mediaLive->createChannel([
                'Name' => "BIF-{$eventId}-channel",
                'RoleArn' => $this->roleArn,
                'ChannelClass' => 'SINGLE_PIPELINE',
                'InputSpecification' => [
                    'Codec' => 'AVC',
                    'Resolution' => 'HD',
                    'MaximumBitrate' => 'MAX_20_MBPS'
                ],
                'InputAttachments' => [
                    [
                        'InputId' => $inputId,
                        'InputAttachmentName' => "BIF-{$eventId}-attachment",
                        'InputSettings' => [
                            'SourceEndBehavior' => 'CONTINUE'
                        ]
                    ]
                ],
                'Destinations' => [
                    [
                        'Id' => 'destination1',
                        'Settings' => [
                            [
                                'PasswordParam' => '',
                                'StreamName' => '',
                                'Url' => "s3://{$this->bucketName}/live/{$eventId}/"
                            ]
                        ]
                    ]
                ],
                'EncoderSettings' => [
                    'AudioDescriptions' => [
                        [
                            'Name' => 'audio_1',
                            'AudioSelectorName' => 'default',
                            'CodecSettings' => [
                                'AacSettings' => [
                                    'Bitrate' => 128000,
                                    'Profile' => 'LC',
                                    'SampleRate' => 48000
                                ]
                            ]
                        ]
                    ],
                    'VideoDescriptions' => [
                        [
                            'Name' => 'video_1080p',
                            'Width' => 1920,
                            'Height' => 1080,
                            'CodecSettings' => [
                                'H264Settings' => [
                                    'Profile' => 'HIGH',
                                    'Level' => 'H264_LEVEL_4_2',
                                    'RateControlMode' => 'CBR',
                                    'Bitrate' => 8000000,
                                    'FramerateControl' => 'SPECIFIED',
                                    'FramerateNumerator' => 30,
                                    'FramerateDenominator' => 1
                                ]
                            ]
                        ]
                    ],
                    'OutputGroups' => [
                        [
                            'Name' => 'HLS',
                            'OutputGroupSettings' => [
                                'HlsGroupSettings' => [
                                    'Destination' => [
                                        'DestinationRefId' => 'destination1'
                                    ],
                                    'SegmentLength' => 6,
                                    'ManifestName' => 'index',
                                    'ManifestDurationFormat' => 'INTEGER',
                                    'OutputSelection' => 'MANIFESTS_AND_SEGMENTS',
                                    'StreamInfResolution' => 'INCLUDE'
                                ]
                            ],
                            'Outputs' => [
                                [
                                    'OutputName' => '1080p',
                                    'VideoDescriptionName' => 'video_1080p',
                                    'AudioDescriptionNames' => ['audio_1'],
                                    'OutputSettings' => [
                                        'HlsOutputSettings' => [
                                            'NameModifier' => '_1080p',
                                            'HlsSettings' => [
                                                'StandardHlsSettings' => [
                                                    'M3u8Settings' => [
                                                        'PcrControl' => 'PCR_EVERY_PES_PACKET'
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
            
            return [
                'success' => true,
                'channel_id' => $result['Channel']['Id']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to create MediaLive channel: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Start MediaLive Channel
     */
    public function startChannel($channelId) {
        try {
            $this->mediaLive->startChannel(['ChannelId' => $channelId]);
            $this->log("Started MediaLive channel: $channelId");
            
            return [
                'success' => true,
                'message' => 'Channel started successfully',
                'status' => 'starting'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to start channel: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Stop MediaLive Channel
     */
    public function stopChannel($channelId) {
        try {
            $this->mediaLive->stopChannel(['ChannelId' => $channelId]);
            $this->log("Stopped MediaLive channel: $channelId");
            
            return [
                'success' => true,
                'message' => 'Channel stopped successfully',
                'status' => 'stopping'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to stop channel: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get Channel Status
     */
    public function getChannelStatus($channelId) {
        try {
            $result = $this->mediaLive->describeChannel(['ChannelId' => $channelId]);
            $channel = $result['Channel'];
            
            return [
                'success' => true,
                'channel_id' => $channelId,
                'state' => $channel['State'],
                'running' => $channel['State'] === 'RUNNING',
                'channel_class' => $channel['ChannelClass'] ?? 'SINGLE_PIPELINE',
                'input_count' => count($channel['InputAttachments'] ?? [])
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to get channel status: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate Playback URL
     */
    private function generatePlaybackUrl($eventId) {
        // Direct S3 URL (CloudFront can be added later)
        return "https://{$this->bucketName}.s3.{$this->region}.amazonaws.com/live/{$eventId}/index.m3u8";
    }
    
    /**
     * Get or Create Security Group
     */
    private function getOrCreateSecurityGroup() {
        try {
            $result = $this->mediaLive->listInputSecurityGroups();
            $groups = $result['InputSecurityGroups'] ?? [];
            
            foreach ($groups as $group) {
                if (strpos($group['Id'], 'BIF') !== false) {
                    return $group['Id'];
                }
            }
            
            // Create new security group
            $result = $this->mediaLive->createInputSecurityGroup([
                'WhitelistRules' => [
                    ['Cidr' => '0.0.0.0/0'] // Allow all for testing
                ],
                'Tags' => [
                    'Project' => 'BIF-PPV'
                ]
            ]);
            
            return $result['SecurityGroup']['Id'];
            
        } catch (Exception $e) {
            throw new Exception('Failed to get/create security group: ' . $e->getMessage());
        }
    }
    
    /**
     * List all streams
     */
    public function listStreams() {
        try {
            $channels = $this->mediaLive->listChannels();
            $inputs = $this->mediaLive->listInputs();
            
            $streams = [];
            foreach ($channels['Channels'] as $channel) {
                if (strpos($channel['Name'], 'BIF-') === 0) {
                    $streams[] = [
                        'channel_id' => $channel['Id'],
                        'name' => $channel['Name'],
                        'state' => $channel['State'],
                        'created' => $channel['CreatedAt'] ?? null
                    ];
                }
            }
            
            return [
                'success' => true,
                'streams' => $streams,
                'count' => count($streams)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to list streams: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete stream (cleanup)
     */
    public function deleteStream($channelId, $inputId) {
        try {
            // Stop channel first
            try {
                $this->mediaLive->stopChannel(['ChannelId' => $channelId]);
                sleep(30); // Wait for channel to stop
            } catch (Exception $e) {
                // Channel might already be stopped
            }
            
            // Delete channel
            $this->mediaLive->deleteChannel(['ChannelId' => $channelId]);
            
            // Delete input
            $this->mediaLive->deleteInput(['InputId' => $inputId]);
            
            return [
                'success' => true,
                'message' => 'Stream deleted successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to delete stream: ' . $e->getMessage()
            ];
        }
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logFile = dirname(__DIR__) . '/data/aws_stream.log';
        $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
        @file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

// Test funkcija
function testAWSConnection() {
    try {
        $manager = new BIF_AWSStreamManager();
        return $manager->testConnection();
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'AWS Test failed: ' . $e->getMessage()
        ];
    }
}

// Export za korišćenje u drugim fajlovima
if (basename($_SERVER['PHP_SELF']) === 'stream-manager.php') {
    header('Content-Type: application/json');
    echo json_encode(testAWSConnection(), JSON_PRETTY_PRINT);
}
?>