<?php

function get(string $url, string $which)
{
    curl_setopt_array($ch = curl_init($url), [
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/95.0.4638.54 Safari/537.36',
    ]);

    $response = $which === 'bunny' ? json_decode(curl_exec($ch)) : array_filter(explode(PHP_EOL, curl_exec($ch)));

    empty($response) and err('[ERROR FETCHING] ' . $url);

    return $response;
}

function err(string $message)
{
    file_put_contents('/var/log/real-ip-error.log', date('Y-m-d H:i:s') . " $message\n", FILE_APPEND);
    die;
}

$map = [
    'bunny' => [
        'X-Real-IP', [
            'https://bunnycdn.com/api/system/edgeserverlist/',
            'https://bunnycdn.com/api/system/edgeserverlist/IPv6',
        ],
    ],
    'cloudflare' => [
        'CF-Connecting-IP', [
            'https://www.cloudflare.com/ips-v4',
            'https://www.cloudflare.com/ips-v6',
        ],
    ],
];

$filePath = __DIR__ . '/../storage/data/real-ips-%s.conf';

foreach ($map as $which => [$header, $urls]) {
    file_put_contents(sprintf($filePath, $which), '');

    foreach ($urls as $url) {
        foreach (get($url, $which) as $ip) {
            file_put_contents(sprintf($filePath, $which), "set_real_ip_from $ip;\n", FILE_APPEND);
        }
    }

    file_put_contents(sprintf($filePath, $which), "set_real_ip_from 172.16.0.0/12;\n", FILE_APPEND);
    file_put_contents(sprintf($filePath, $which), "real_ip_header $header;\n", FILE_APPEND);
}
