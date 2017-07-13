<?php
namespace DexterLiu\ApiUtil;

class Util
{
    public static function embed($request, $query)
    {
        function getFilterOfEmbed($embed, $filters)
        {
            return collect($filters)->filter(function ($filter, $key) use ($embed) {
                return starts_with($key, "$embed.");
            });
        }
        if ($request->has('embed')) {
            $embed = $request->input('embed', []);
            $relations = explode(',', $embed);
            foreach ($relations as $relation) {
                $filters = getFilterOfEmbed($relation, $request->input('filter', []));
                if ($filters->count()) {
                    foreach ($filters as $key => $value) {
                        $filterRelation = collect(explode('.', $key));
                        $filterField = $filterRelation->pop();
                        $filterRelation = $filterRelation->implode('.');
                        if ($filterRelation == $relation) {
                            $filterValue = explode(',', $value);
                            $query->with([$relation => function ($q) use ($filterField, $filterValue) {
                                $q->whereIn($filterField, $filterValue);
                            }]);
                        }
                    }
                } else {
                    $query->with($relation);
                }
            }
        }
    }

    public static function filter($request, $query)
    {
        $filters = collect($request->input('filter', []));
        $filters = $filters->filter(function ($filter, $key) {
            return !str_contains($key, '.');
        });
        foreach ($filters as $key => $value) {
            if (!is_null($value)) {
                $value = explode(',', $value);
                $query->whereIn($key, $value);
            }
        }
    }

    public static function sort($request, $query)
    {
        if ($request->has('sort')) {
            $sort = $request->input('sort', 'id');
            $direction = starts_with($sort, '-') ? 'desc' : 'asc';
            $sortField = ltrim($sort, '-');
            $query->orderBy($sortField, $direction);
        }
    }

    public static function groupBy($request, $query)
    {
        if ($request->has('group')) {
            $field = $request->group;
            $query->groupBy($field);
        }
    }

    public static function paginate($request, $query)
    {
        $perPage = $request->input('per-page', 10);
        $result = $query->paginate($perPage);
        return $result;
    }

    public static function getDataWithParams($request, $query, $id = 0)
    {
        self::embed($request, $query);
        self::filter($request, $query);
        self::sort($request, $query);
        self::groupBy($request, $query);

        if ($id) {
            return $query->find($id);
        }

        if ($request->has('page')) {
            return self::paginate($request, $query);
        }

        return $query->get();
    }
}
