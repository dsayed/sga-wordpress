<?php
/**
 * RescueGroups API v2 client.
 *
 * API docs: https://userguide.rescuegroups.org/api/
 * Endpoint: POST https://api.rescuegroups.org/http/v2.json
 */

if (!defined('ABSPATH')) exit;

class SGA_RescueGroups_API_Client {

    private string $endpoint = 'https://api.rescuegroups.org/http/v2.json';
    private string $api_key;
    private string $org_id;
    private int $cache_ttl;

    public function __construct() {
        $this->api_key   = SGA_RG_API_KEY;
        $this->org_id    = SGA_RG_ORG_ID;
        $this->cache_ttl = SGA_RG_CACHE_TTL;
    }

    /**
     * Get all available dogs for SGA.
     *
     * @return array|WP_Error Array of dog data or error.
     */
    public function get_available_dogs(): array|WP_Error {
        $cache_key = 'sga_rg_dogs_all';
        $cached = get_transient($cache_key);
        if ($cached !== false) return $cached;

        $body = [
            'apikey'     => $this->api_key,
            'objectType' => 'animals',
            'objectAction' => 'publicSearch',
            'search' => [
                'resultStart' => 0,
                'resultLimit' => 100,
                'resultSort'  => 'animalName',
                'resultOrder' => 'asc',
                'filters' => [
                    [
                        'fieldName'   => 'animalOrgID',
                        'operation'   => 'equals',
                        'criteria'    => $this->org_id,
                    ],
                    [
                        'fieldName'   => 'animalStatus',
                        'operation'   => 'equals',
                        'criteria'    => 'Available',
                    ],
                ],
                'fields' => [
                    'animalID', 'animalName', 'animalBreed', 'animalSex',
                    'animalGeneralAge', 'animalColor', 'animalCoatLength',
                    'animalHousetrained', 'animalDescription', 'animalPictures',
                    'animalSpecies', 'animalSizeCurrent',
                ],
            ],
        ];

        $response = wp_remote_post($this->endpoint, [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => wp_json_encode($body),
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) return $response;

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($data) || ($data['status'] ?? '') !== 'ok') {
            return new WP_Error('api_error', 'RescueGroups API returned an error.');
        }

        // The API nests results under 'data' with string keys
        $dogs = [];
        if (!empty($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $dog) {
                $dogs[] = $this->normalize_dog($dog);
            }
        }

        // Sort by name
        usort($dogs, fn($a, $b) => strcasecmp($a['name'], $b['name']));

        set_transient($cache_key, $dogs, $this->cache_ttl);
        return $dogs;
    }

    /**
     * Get a single dog by ID.
     */
    public function get_dog(int $id): array|WP_Error {
        // Try cache first
        $all = get_transient('sga_rg_dogs_all');
        if (is_array($all)) {
            foreach ($all as $dog) {
                if ((int)$dog['id'] === $id) return $dog;
            }
        }

        // Fetch individually
        $body = [
            'apikey'       => $this->api_key,
            'objectType'   => 'animals',
            'objectAction' => 'publicSearch',
            'search' => [
                'resultLimit' => 1,
                'filters' => [
                    [
                        'fieldName' => 'animalID',
                        'operation' => 'equals',
                        'criteria'  => (string)$id,
                    ],
                ],
                'fields' => [
                    'animalID', 'animalName', 'animalBreed', 'animalSex',
                    'animalGeneralAge', 'animalColor', 'animalCoatLength',
                    'animalHousetrained', 'animalDescription', 'animalPictures',
                    'animalSpecies', 'animalSizeCurrent',
                ],
            ],
        ];

        $response = wp_remote_post($this->endpoint, [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => wp_json_encode($body),
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) return $response;

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data['data'])) {
            return new WP_Error('not_found', 'Dog not found.');
        }

        $first = reset($data['data']);
        return $this->normalize_dog($first);
    }

    /**
     * Get the count of available dogs.
     */
    public function get_available_count(): int|WP_Error {
        $dogs = $this->get_available_dogs();
        if (is_wp_error($dogs)) return $dogs;
        return count($dogs);
    }

    /**
     * Normalize API response into a clean array.
     */
    private function normalize_dog(array $raw): array {
        $photos = [];
        if (!empty($raw['animalPictures'])) {
            foreach ($raw['animalPictures'] as $pic) {
                $photos[] = [
                    'original' => $pic['original']['url'] ?? '',
                    'large'    => $pic['large']['url'] ?? '',
                    'small'    => $pic['small']['url'] ?? '',
                ];
            }
        }

        // Extract first sentence for tagline
        $desc = $raw['animalDescription'] ?? '';
        $tagline = '';
        if ($desc) {
            $text = wp_strip_all_tags($desc);
            $text = preg_replace('/\s+/', ' ', trim($text));
            if (preg_match('/^(.+?[.!?])\s/', $text, $m)) {
                $tagline = $m[1];
            }
        }

        return [
            'id'           => $raw['animalID'] ?? '',
            'name'         => $raw['animalName'] ?? '',
            'breed'        => $raw['animalBreed'] ?? '',
            'sex'          => $raw['animalSex'] ?? '',
            'age'          => $raw['animalGeneralAge'] ?? '',
            'color'        => $raw['animalColor'] ?? '',
            'coat_length'  => $raw['animalCoatLength'] ?? '',
            'housetrained' => $raw['animalHousetrained'] ?? '',
            'size'         => self::weight_to_size($raw['animalSizeCurrent'] ?? ''),
            'description'  => $desc,
            'tagline'      => $tagline,
            'photos'       => $photos,
        ];
    }

    /**
     * Convert weight in lbs to a size category.
     */
    private static function weight_to_size(string $weight): string {
        $lbs = (float) $weight;
        if ($lbs <= 0) return '';
        if ($lbs < 25) return 'Small';
        if ($lbs < 50) return 'Medium';
        if ($lbs < 80) return 'Large';
        return 'X-Large';
    }
}
