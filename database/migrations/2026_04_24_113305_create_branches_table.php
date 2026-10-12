<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('branches')) {
            $this->addMissingColumns();
        } else {
            $this->createFreshTable();
        }

        $this->fixCodeUniqueIndex();
    }

    private function addMissingColumns(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (! Schema::hasColumn('branches', 'approval_status')) {
                $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            }
            if (! Schema::hasColumn('branches', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }
            if (! Schema::hasColumn('branches', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable();
            }
            if (! Schema::hasColumn('branches', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable();
            }
            if (! Schema::hasColumn('branches', 'branch_manager_id')) {
                $table->unsignedBigInteger('branch_manager_id')->nullable();
            }
            if (! Schema::hasColumn('branches', 'email')) {
                $table->string('email')->nullable();
            }
            if (! Schema::hasColumn('branches', 'description')) {
                $table->text('description')->nullable();
            }
            if (! Schema::hasColumn('branches', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
            }
            if (! Schema::hasColumn('branches', 'settings')) {
                $table->json('settings')->nullable();
            }

            if (! Schema::hasColumn('branches', 'barangay')) {
                $table->string('barangay')->nullable()->after('address');
            }
            if (! Schema::hasColumn('branches', 'city')) {
                $table->string('city')->nullable()->after('barangay');
            }
            if (! Schema::hasColumn('branches', 'province')) {
                $table->string('province')->nullable()->after('city');
            }

            if (! Schema::hasColumn('branches', 'manager_name')) {
                $table->string('manager_name')->nullable()->after('contact_number');
            }
            if (! Schema::hasColumn('branches', 'manager_id_path')) {
                $table->string('manager_id_path')->nullable()->after('manager_name');
            }
            if (! Schema::hasColumn('branches', 'permit_path')) {
                $table->string('permit_path')->nullable()->after('manager_id_path');
            }
            if (! Schema::hasColumn('branches', 'location_photo_path')) {
                $table->string('location_photo_path')->nullable()->after('permit_path');
            }

            if (! Schema::hasColumn('branches', 'business_permit_path')) {
                $table->string('business_permit_path')->nullable()->after('permit_path');
            }
            if (! Schema::hasColumn('branches', 'dti_sec_registration_path')) {
                $table->string('dti_sec_registration_path')->nullable()->after('business_permit_path');
            }
            if (! Schema::hasColumn('branches', 'bir_certificate_path')) {
                $table->string('bir_certificate_path')->nullable()->after('dti_sec_registration_path');
            }
            if (! Schema::hasColumn('branches', 'barangay_clearance_path')) {
                $table->string('barangay_clearance_path')->nullable()->after('bir_certificate_path');
            }
            if (! Schema::hasColumn('branches', 'sanitary_permit_path')) {
                $table->string('sanitary_permit_path')->nullable()->after('barangay_clearance_path');
            }
            if (! Schema::hasColumn('branches', 'fire_safety_path')) {
                $table->string('fire_safety_path')->nullable()->after('sanitary_permit_path');
            }

            if (! Schema::hasColumn('branches', 'dti_sec_number')) {
                $table->string('dti_sec_number')->nullable()->after('fire_safety_path');
            }
            if (! Schema::hasColumn('branches', 'bir_tin')) {
                $table->string('bir_tin')->nullable()->after('dti_sec_number');
            }
            if (! Schema::hasColumn('branches', 'mayors_permit_number')) {
                $table->string('mayors_permit_number')->nullable()->after('bir_tin');
            }

            if (! Schema::hasColumn('branches', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('name');
            }
        });

        if (Schema::hasTable('users')) {
            Schema::table('branches', function (Blueprint $table) {
                if (Schema::hasColumn('branches', 'approved_by') && ! $this->hasForeignKey('branches', 'approved_by')) {
                    $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
                }
                if (Schema::hasColumn('branches', 'branch_manager_id') && ! $this->hasForeignKey('branches', 'branch_manager_id')) {
                    $table->foreign('branch_manager_id')->references('id')->on('users')->nullOnDelete();
                }
                if (Schema::hasColumn('branches', 'created_by') && ! $this->hasForeignKey('branches', 'created_by')) {
                    $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                }
            });
        }

        if (Schema::hasColumn('branches', 'approval_status')) {
            DB::table('branches')
                ->whereNull('approval_status')
                ->update([
                    'approval_status' => 'approved',
                    'approved_at'     => now(),
                ]);
        }
    }

    private function createFreshTable(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('logo_path')->nullable();
            $table->string('code')->nullable();
            $table->string('address')->nullable();
            $table->string('barangay')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('contact_number')->nullable();

            $table->string('manager_name')->nullable();
            $table->string('manager_id_path')->nullable();
            $table->string('permit_path')->nullable();
            $table->string('location_photo_path')->nullable();

            $table->string('business_permit_path')->nullable();
            $table->string('dti_sec_registration_path')->nullable();
            $table->string('bir_certificate_path')->nullable();
            $table->string('barangay_clearance_path')->nullable();
            $table->string('sanitary_permit_path')->nullable();
            $table->string('fire_safety_path')->nullable();

            $table->string('dti_sec_number')->nullable();
            $table->string('bir_tin')->nullable();
            $table->string('mayors_permit_number')->nullable();

            $table->string('email')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->enum('approval_status', ['pending', 'approved', 'rejected'])
                  ->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('branch_manager_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index('approval_status');
            $table->index('tenant_id');
            $table->index('is_active');
        });
    }

    private function fixCodeUniqueIndex(): void
    {
        if (! Schema::hasTable('branches')) {
            return;
        }

        if ($this->hasIndex('branches', 'branches_code_unique')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropUnique('branches_code_unique');
            });
        }

        if (! $this->hasIndex('branches', 'branches_tenant_id_code_unique')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->unique(['tenant_id', 'code'], 'branches_tenant_id_code_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('branches')) {
            return;
        }

        if ($this->hasIndex('branches', 'branches_tenant_id_code_unique')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropUnique('branches_tenant_id_code_unique');
            });
        }

        Schema::table('branches', function (Blueprint $table) {
            foreach (['approved_by', 'branch_manager_id', 'created_by'] as $fk) {
                if ($this->hasForeignKey('branches', $fk)) {
                    $table->dropForeign([$fk]);
                }
            }

            $columns = [
                'approval_status', 'approved_at', 'approved_by', 'rejection_reason',
                'branch_manager_id', 'email', 'description', 'created_by', 'settings',
                'barangay', 'city', 'province',
                'manager_name', 'manager_id_path', 'permit_path', 'location_photo_path',
                'business_permit_path', 'dti_sec_registration_path', 'bir_certificate_path',
                'barangay_clearance_path', 'sanitary_permit_path', 'fire_safety_path',
                'dti_sec_number', 'bir_tin', 'mayors_permit_number',
                'logo_path',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('branches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function hasForeignKey($table, $column): bool
    {
        $foreignKeys = collect(DB::select(
            "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL",
            [$table, $column]
        ));
        return $foreignKeys->isNotEmpty();
    }

    private function hasIndex($table, $index): bool
    {
        return collect(DB::select(
            "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?",
            [$table, $index]
        ))->isNotEmpty();
    }
};