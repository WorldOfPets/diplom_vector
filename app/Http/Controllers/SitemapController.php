<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $lastModified = now()->startOfDay();

        $pages = [
            ['url' => route('home'), 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['url' => route('credits'), 'priority' => '0.4', 'changefreq' => 'yearly'],
            ['url' => route('privacy'), 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($pages as $page) {
            $xml .= '    <url>'."\n";
            $xml .= '        <loc>'.e($page['url']).'</loc>'."\n";
            $xml .= '        <lastmod>'.$lastModified->toDateString().'</lastmod>'."\n";
            $xml .= '        <changefreq>'.$page['changefreq'].'</changefreq>'."\n";
            $xml .= '        <priority>'.$page['priority'].'</priority>'."\n";
            $xml .= '    </url>'."\n";
        }

        $xml .= '</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
