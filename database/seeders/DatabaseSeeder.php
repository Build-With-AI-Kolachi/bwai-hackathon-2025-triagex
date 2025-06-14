<?php

namespace Database\Seeders;
use App\Models\User;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'), // Or use Hash::make('password')
        ]);

          // Seed some dummy teams
          \App\Models\Team::firstOrCreate(['name' => 'Tech', 'email_alias' => 'tech@neem.com', 'slack_channel' => '#tech-alerts']);
          \App\Models\Team::firstOrCreate(['name' => 'Ops', 'email_alias' => 'ops@neem.com', 'slack_channel' => '#ops-alerts']);
          \App\Models\Team::firstOrCreate(['name' => 'Product', 'email_alias' => 'product@neem.com', 'slack_channel' => '#product-alerts']);
          \App\Models\Team::firstOrCreate(['name' => 'Finance', 'email_alias' => 'finance@neem.com', 'slack_channel' => '#finance-alerts']);
          \App\Models\Team::firstOrCreate(['name' => 'Sales', 'email_alias' => 'sales@neem.com', 'slack_channel' => '#sales-alerts']);
          \App\Models\Team::firstOrCreate(['name' => 'Tech Lead', 'email_alias' => 'techlead@neem.com', 'slack_channel' => '#tech-lead-alerts']);
  
          $this->call(KnowledgeBaseArticlesSeeder::class);
    }
}
