<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginVisualizations\Concerns;

use Dashworthy\Visualizations\Contracts\VisualizationContract;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Response;
use Illuminate\Testing\TestResponse;

/**
 * Endpoint assertions for a visualization, resolved from the class itself.
 *
 * A visualization is two things that fail independently: a class that builds a
 * query, and the pair of routes it is served over. The rest of each tester
 * covers the first; this covers the second.
 *
 * Nothing here is passed a route name. VisualizationsServiceProvider registers
 * every visualization at $visualization->getRouteName().'.schema' and '.data',
 * so the tester derives both from the class under test — a renamed or
 * unregistered visualization fails here rather than silently testing a route
 * that no longer matches the class.
 */
trait AssertsVisualizationEndpoints
{
    private ?Authenticatable $endpointUser = null;

    private ?string $endpointGuard = null;

    /** @var array<string, mixed> */
    private array $endpointSession = [];

    /**
     * The visualization under test. Each tester holds it under its own name.
     */
    abstract protected function visualization(): VisualizationContract;

    /**
     * Authenticate the requests this tester makes.
     *
     * The guard is explicit because a visualization served to more than one
     * guard is exactly the case worth asserting: the same route answering an
     * administrator and refusing a customer.
     */
    public function actingAs(Authenticatable $user, ?string $guard = null): static
    {
        $this->endpointUser = $user;
        $this->endpointGuard = $guard;

        return $this;
    }

    /**
     * Session state the endpoints require — a passed two-factor challenge, a
     * recent password confirmation, anything the middleware reads.
     *
     * @param  array<string, mixed>  $session
     */
    public function withSession(array $session): static
    {
        $this->endpointSession = [...$this->endpointSession, ...$session];

        return $this;
    }

    /**
     * Drop the authenticated actor, so subsequent assertions run as a guest.
     */
    public function asGuest(): static
    {
        $this->endpointUser = null;
        $this->endpointGuard = null;
        $this->endpointSession = [];

        return $this;
    }

    public function schemaRouteName(): string
    {
        return $this->visualization()->getRouteName().'.schema';
    }

    public function dataRouteName(): string
    {
        return $this->visualization()->getRouteName().'.data';
    }

    /**
     * The schema endpoint answers, and answers for this visualization. The key
     * is asserted because every visualization's schema route has the same
     * shape: without it a test would pass against the wrong grid entirely.
     */
    public function assertSchemaServed(): static
    {
        $this->postToEndpoint($this->schemaRouteName())
            ->assertOk()
            ->assertJsonPath('visualization_key', $this->visualization()->getVisualizationKey());

        return $this;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function assertDataServed(array $payload = ['per_page' => 50, 'page' => 1]): static
    {
        $this->postToEndpoint($this->dataRouteName(), $payload)
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);

        return $this;
    }

    public function assertEndpointsServed(): static
    {
        return $this->assertSchemaServed()->assertDataServed();
    }

    public function assertSchemaForbidden(): static
    {
        $this->postToEndpoint($this->schemaRouteName())->assertForbidden();

        return $this;
    }

    public function assertDataForbidden(): static
    {
        $this->postToEndpoint($this->dataRouteName())->assertForbidden();

        return $this;
    }

    public function assertEndpointsForbidden(): static
    {
        return $this->assertSchemaForbidden()->assertDataForbidden();
    }

    /**
     * Neither endpoint answers a guest.
     *
     * Asserted as "not 200" rather than as a redirect to a named login, because
     * where an unauthenticated request is sent is the application's business
     * and differs per guard. What belongs to the visualization is that the data
     * does not come back.
     */
    public function assertEndpointsRefuseGuests(): static
    {
        $user = $this->endpointUser;
        $guard = $this->endpointGuard;
        $session = $this->endpointSession;

        $this->asGuest();

        foreach ([$this->schemaRouteName(), $this->dataRouteName()] as $route) {
            $response = $this->postToEndpoint($route);

            expect($response->getStatusCode())->not->toBe(
                200,
                "Route [{$route}] answered a guest with 200."
            );
        }

        $this->endpointUser = $user;
        $this->endpointGuard = $guard;
        $this->endpointSession = $session;

        return $this;
    }

    /**
     * The visualization declares the permission its endpoints authorise with.
     *
     * DataGrid, Chart and Metric all call Gate::authorize(getPermissionName())
     * only when the method exists, so a visualization that never declares one
     * is served to anybody who can reach the route.
     */
    public function assertDeclaresPermission(?string $permission = null): static
    {
        $visualization = $this->visualization();

        $declares = method_exists($visualization, 'getPermissionName');

        expect($declares)->toBeTrue(
            $visualization::class.' declares no permission, so its endpoints authorise nobody.'
        );

        if ($permission !== null) {
            // Resolved through a callable rather than called directly:
            // getPermissionName() comes from the HasVisualizationPermissions
            // trait rather than the contract, so a visualization may
            // legitimately not have it — which is what the assertion above is
            // for, and why the contract cannot declare it.
            /** @var callable(): string $resolvePermission */
            $resolvePermission = [$visualization, 'getPermissionName'];

            expect($resolvePermission())->toBe($permission);
        }

        return $this;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return TestResponse<Response>
     */
    private function postToEndpoint(string $route, array $payload = []): TestResponse
    {
        // test() is typed as TestCall|HigherOrderTapProxy, and the proxy
        // forwards to the underlying TestCase at runtime — which is where
        // actingAs(), withSession() and post() live. The other testers need
        // the same narrowing for their test()->fail() calls.
        /** @var TestCase $pending */
        $pending = test(); // @phpstan-ignore varTag.nativeType

        if ($this->endpointUser !== null) {
            $pending = $pending->actingAs($this->endpointUser, $this->endpointGuard);
        }

        if ($this->endpointSession !== []) {
            $pending = $pending->withSession($this->endpointSession);
        }

        /** @var TestResponse<Response> */
        return $pending->post(route($route), $payload);
    }
}
