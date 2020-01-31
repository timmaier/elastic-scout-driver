<?php
declare(strict_types=1);

namespace ElasticScoutDriver\Factories;

use ElasticAdapter\Search\Hit;
use ElasticAdapter\Search\SearchResponse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Builder;

final class ModelFactory implements ModelFactoryInterface
{
    public function makeFromSearchResponse(SearchResponse $searchResponse, Builder $builder): Collection
    {
        if ($searchResponse->getHitsTotal() == 0) {
            return $builder->model->newCollection();
        }

        $documentIds = collect($searchResponse->getHits())->map(function (Hit $hit) {
            return $hit->getDocument()->getId();
        })->all();

        $documentIdPositions = array_flip($documentIds);

        return $builder->model->getScoutModelsByIds($builder, $documentIds)
            ->filter(function (Model $model) use ($documentIds) {
                return in_array($model->getScoutKey(), $documentIds);
            })
            ->sortBy(function (Model $model) use ($documentIdPositions) {
                return $documentIdPositions[$model->getScoutKey()];
            })
            ->values();
    }
}
