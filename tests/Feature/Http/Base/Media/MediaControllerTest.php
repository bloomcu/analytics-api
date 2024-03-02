<?php

namespace Tests\Feature\Http\Base\Media;

use Tests\TestCase;

/**
 * @see \DDD\Http\Base\Media\MediaController
 */
class MediaControllerTest extends TestCase
{
    /**
     * @test
     */
    public function destroy_returns_an_ok_response(): void
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->deleteJson('api/{organization}/media/{media}');

        $response->assertOk();
        $response->assertJsonStructure([
            // TODO: compare expected response data
        ]);
        $this->assertModelMissing($media);

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function index_returns_an_ok_response(): void
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->getJson('api/{organization}/media');

        $response->assertOk();
        $response->assertJsonStructure([
            // TODO: compare expected response data
        ]);

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function show_returns_an_ok_response(): void
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->getJson('api/{organization}/media/{media}');

        $response->assertOk();
        $response->assertJsonStructure([
            // TODO: compare expected response data
        ]);

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function store_validates_with_a_form_request(): void
    {
        $this->assertActionUsesFormRequest(
            \DDD\Http\Base\Media\MediaController::class,
            'store',
            \DDD\Domain\Base\Media\Requests\StoreMediaRequest::class
        );
    }

    /**
     * @test
     */
    public function store_returns_an_ok_response(): void
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->postJson('api/{organization}/media', [
            // TODO: send request data
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            // TODO: compare expected response data
        ]);

        // TODO: perform additional assertions
    }

    // test cases...
}
