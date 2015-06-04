<?php namespace App\Models;

class SoftVersions {

    public static function getAll() {
        $result = [];

        foreach (range(1, 2) as $device_type) {
            $result[$device_type] = \DB::select("
                SELECT *
                    FROM public.soft_versions
                    WHERE device_type = $device_type
                    ORDER BY id DESC;
            ");

            foreach ($result[$device_type] as $version) {
                $version->id = Helper::softVersionFromIntToString($version->id);
            }
        }

        return $result;
    }

    public static function findById($id, $device_type) {
        $id = Helper::softVersionFromStringToInt($id);
        $data = \DB::select("
            SELECT *
                FROM public.soft_versions
                WHERE   id = ? AND
                        device_type = ?;
        ", [$id, $device_type]);

        if ($data) {
            $version = $data[0];
            $version->id = Helper::softVersionFromIntToString($version->id);
            return $version;
        }

        return null;
    }

    public static function makeActual($id, $device_type, $actual) {
        $id = Helper::softVersionFromStringToInt($id);
        \DB::select("
            UPDATE public.soft_versions
                SET is_actual = ?
                WHERE   id = ? AND
                        device_type = ?;
        ", [$actual, $id, $device_type]);
    }

    public static function upsert($id, $device_type, $description) {
        $id = Helper::softVersionFromStringToInt($id);
        if (! $id) {
            return $id;
        }

        \DB::select("
            DO $$
            DECLARE
            BEGIN

                UPDATE public.soft_versions
                    SET description = :description
                    WHERE   id = :id AND
                            device_type = :device_type;

                IF NOT FOUND THEN
                    INSERT INTO public.soft_versions
                        (id, description, device_type, is_actual)
                        VALUES (
                            :id, :description, :device_type, 'f'
                        );
                END IF;

            END;$$;
        ", [
            'id' => $id,
            'description' => $description,
            'device_type' => $device_type,
        ]);

        return true;
    }

}
