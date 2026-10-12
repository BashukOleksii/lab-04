<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_home_page_redirects_to_repairs_list(): void
    {
        $this->get('/')->assertRedirect(route('repair-requests.index'));
    }
}
