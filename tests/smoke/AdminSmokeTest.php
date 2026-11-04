<?php

namespace Tests\Feature;

use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Auth\Database\AdminTablesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * End-to-end smoke test of the admin panel, copied into a fresh Laravel app by setup-app.sh.
 *
 * It collects every failure and deprecation instead of stopping at the first one, so a
 * single run shows everything that broke on a given Laravel / PHP combination.
 */
class AdminSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected $failures = [];

    protected $deprecations = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AdminTablesSeeder::class);
        config(['admin.upload.disk' => 'admin', 'filesystems.disks.admin' => [
            'driver' => 'local', 'root' => storage_path('app/admin-test'), 'url' => '/uploads',
        ]]);

        // collect every deprecation / warning instead of only logging it
        set_error_handler(function ($level, $message, $file, $line) {
            $this->deprecations[] = "$message @ ".str_replace(base_path(), '', $file).":$line";

            return true;
        }, E_ALL);
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        parent::tearDown();
    }

    protected function check($label, $response, $expected = [200])
    {
        $status = $response->getStatusCode();
        if (!in_array($status, (array) $expected)) {
            $e = $response->exception ?? null;
            $this->failures[] = sprintf('%s => HTTP %d %s', $label, $status,
                $e ? get_class($e).': '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine() : substr(strip_tags($response->getContent()), 0, 300));
        }

        // laravel-admin catches exceptions thrown while rendering a page (Content::build())
        // and shows them inside a 200 page: treat that as a failure too
        $content = (string) $response->getContent();
        if ($status === 200 && strpos($content, 'laravel-admin-exception-trace') !== false) {
            preg_match('/laravel-admin-exception-trace.*?<\/i>(.*?)<\/a>/s', $content, $m);
            $this->failures[] = sprintf('%s => exception rendered in the page: %s', $label,
                trim(html_entity_decode(strip_tags($m[1] ?? 'unknown'))));
        }

        return $response;
    }

    public function test_admin_panel_smoke()
    {
        $prefix = '/'.trim(config('admin.route.prefix'), '/');

        // guest
        $this->check('login page', $this->get("$prefix/auth/login"));
        $this->check('guest redirect', $this->get("$prefix"), [302]);
        $this->check('login post', $this->post("$prefix/auth/login", ['username' => 'admin', 'password' => 'admin']), [302]);
        $this->check('dashboard after login', $this->get("$prefix"));
        $this->post("$prefix/auth/logout");
        $this->get("$prefix/auth/logout");

        $admin = Administrator::first();
        $this->actingAs($admin, 'admin');

        $pages = [
            'dashboard'          => '',
            'users grid'         => '/auth/users',
            'users sorted'       => '/auth/users?_sort[column]=name&_sort[type]=desc',
            'users filtered'     => '/auth/users?username=adm&name=Admin',
            'users quick search' => '/auth/users?__search__=adm',
            'users per page'     => '/auth/users?per_page=10&page=1',
            'users create'       => '/auth/users/create',
            'users edit'         => '/auth/users/1/edit',
            'users show'         => '/auth/users/1',
            'roles grid'         => '/auth/roles',
            'roles create'       => '/auth/roles/create',
            'roles edit'         => '/auth/roles/1/edit',
            'permissions grid'   => '/auth/permissions',
            'permissions create' => '/auth/permissions/create',
            'permissions edit'   => '/auth/permissions/1/edit',
            'menu tree'          => '/auth/menu',
            'menu edit'          => '/auth/menu/1/edit',
            'logs grid'          => '/auth/logs',
            'setting'            => '/auth/setting',
        ];
        foreach ($pages as $label => $uri) {
            $this->check($label, $this->get($prefix.$uri));
        }

        // grid features the built-in controllers don't use
        \Illuminate\Support\Facades\Route::middleware(config('admin.route.middleware'))->get("$prefix/smoke-grid", function (\Encore\Admin\Layout\Content $content) {
            $grid = new \Encore\Admin\Grid(new Administrator());
            $grid->column('username')->expand(function () {
                return "expanded {$this->username}";
            });
            $grid->column('name')->modal('Modal', function () {
                return "modal {$this->name}";
            });
            $grid->quickSearch('username');
            // inline radio on a null value (no such attribute)
            $grid->column('smoke_null')->radio(['a' => 'A', 'b' => 'B']);

            return $content->body($grid);
        });
        $this->check('grid expand/modal/quick search/inline radio on null', $this->get("$prefix/smoke-grid?__search__=adm"));

        // pjax (symfony/dom-crawler)
        $this->check('pjax users grid', $this->get("$prefix/auth/users", ['X-PJAX' => 'true', 'X-PJAX-Container' => '#pjax-container']));

        // export calls exit(), it is checked through a real HTTP request instead

        // user CRUD with avatar upload
        $this->check('user store', $this->post("$prefix/auth/users", [
            'username'              => 'editor',
            'name'                  => 'Editor',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
            'roles'                 => [1, ''],
            'permissions'           => [''],
            'avatar'                => UploadedFile::fake()->image('avatar.jpg', 50, 50),
        ]), [302]);
        $user = Administrator::where('username', 'editor')->first();
        if (!$user) {
            $this->failures[] = 'user store => user not created';
        } else {
            $this->check('user update', $this->put("$prefix/auth/users/{$user->id}", [
                'username'              => 'editor',
                'name'                  => 'Editor 2',
                'password'              => $user->password,
                'password_confirmation' => $user->password,
                'roles'                 => [1, ''],
                'permissions'           => [''],
            ]), [302]);
            $this->check('user show (with avatar)', $this->get("$prefix/auth/users/{$user->id}"));
            $this->check('users grid (2 rows)', $this->get("$prefix/auth/users"));
            $this->check('row action delete', $this->post("$prefix/_handle_action_", [
                '_key'    => $user->id,
                '_model'  => str_replace('\\', '_', Administrator::class),
                '_action' => str_replace('\\', '_', \Encore\Admin\Grid\Actions\Delete::class),
            ]));
            if (Administrator::find($user->id)) {
                $this->failures[] = 'row action delete => user still exists';
            }
        }

        // role / permission / menu writes
        $this->check('role store', $this->post("$prefix/auth/roles", ['slug' => 'editor', 'name' => 'Editor', 'permissions' => [1, '']]), [302]);
        $this->check('permission store', $this->post("$prefix/auth/permissions", ['slug' => 'posts', 'name' => 'Posts', 'http_method' => ['GET', ''], 'http_path' => '/posts*']), [302]);
        // browsers send parent_id as the string "0"
        $this->check('menu store', $this->post("$prefix/auth/menu", ['parent_id' => '0', 'title' => 'Posts', 'icon' => 'fa-bars', 'uri' => 'posts', 'roles' => [1, '']]), [302]);
        $posts = \Encore\Admin\Auth\Database\Menu::where('title', 'Posts')->first();
        if (!$posts) {
            $this->failures[] = 'menu store => menu not created';
        } else {
            $this->check('menu reorder', $this->post("$prefix/auth/menu", ['_order' => json_encode([['id' => 1, 'children' => [['id' => $posts->id]]], ['id' => 2]])]), [200, 302]);
            if ((int) $posts->fresh()->parent_id !== 1) {
                $this->failures[] = 'menu reorder => parent_id not saved';
            }
        }
        $this->check('menu tree after writes', $this->get("$prefix/auth/menu"));
        $this->check('setting update', $this->put("$prefix/auth/setting", ['name' => 'Administrator', 'password' => $admin->password, 'password_confirmation' => $admin->password]), [302]);

        // generators (native schema introspection on Laravel 11+)
        $this->withoutMockingConsoleOutput();
        try {
            Artisan::call('admin:make', ['name' => 'SmokeUserController', '--model' => Administrator::class, '--output' => true]);
            $out = Artisan::output();
            foreach (["\$grid->column('username'", "\$form->text('username'", "\$show->field('username'"] as $needle) {
                if (strpos($out, $needle) === false) {
                    $this->failures[] = "admin:make output missing $needle";
                }
            }
            Artisan::call('admin:form', ['name' => 'SmokeForm']);
        } catch (\Throwable $e) {
            $this->failures[] = 'generators => '.get_class($e).': '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine();
        }

        $deprecations = array_values(array_unique($this->deprecations));
        fwrite(STDERR, "\n== DEPRECATIONS (".count($deprecations).")\n".implode("\n", $deprecations)."\n");

        foreach ($deprecations as $deprecation) {
            $this->failures[] = "deprecation => $deprecation";
        }

        $this->assertSame([], $this->failures, implode("\n", $this->failures));
    }
}
