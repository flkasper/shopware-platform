<?php declare(strict_types=1);

namespace Shopware\OrderAddress\Writer;

use Shopware\Context\Struct\TranslationContext;
use Shopware\Framework\Event\NestedEventDispatcherInterface;
use Shopware\Framework\Write\FieldAware\DefaultExtender;
use Shopware\Framework\Write\FieldAware\FieldExtenderCollection;
use Shopware\Framework\Write\FieldException\WriteStackException;
use Shopware\Framework\Write\WriteContext;
use Shopware\Framework\Write\Writer;
use Shopware\OrderAddress\Event\OrderAddressWriteExtenderEvent;
use Shopware\OrderAddress\Event\OrderAddressWrittenEvent;
use Shopware\OrderAddress\Writer\Resource\OrderAddressWriteResource;
use Shopware\Shop\Writer\Resource\ShopWriteResource;

class OrderAddressWriter
{
    /**
     * @var DefaultExtender
     */
    private $extender;

    /**
     * @var NestedEventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var Writer
     */
    private $writer;

    public function __construct(DefaultExtender $extender, NestedEventDispatcherInterface $eventDispatcher, Writer $writer)
    {
        $this->extender = $extender;
        $this->eventDispatcher = $eventDispatcher;
        $this->writer = $writer;
    }

    public function update(array $data, TranslationContext $context): OrderAddressWrittenEvent
    {
        $writeContext = $this->createWriteContext($context->getShopUuid());
        $extender = $this->getExtender();

        $this->validateWriteInput($data);

        $updated = $errors = [];

        foreach ($data as $orderAddress) {
            try {
                $updated[] = $this->writer->update(
                    OrderAddressWriteResource::class,
                    $orderAddress,
                    $writeContext,
                    $extender
                );
            } catch (WriteStackException $exception) {
                $errors[] = $exception->toArray();
            }
        }

        $affected = count($updated);
        if ($affected === 1) {
            $updated = array_shift($updated);
        } elseif ($affected > 1) {
            $updated = array_merge_recursive(...$updated);
        }

        return OrderAddressWriteResource::createWrittenEvent($updated, $context, $errors);
    }

    public function upsert(array $data, TranslationContext $context): OrderAddressWrittenEvent
    {
        $writeContext = $this->createWriteContext($context->getShopUuid());
        $extender = $this->getExtender();

        $this->validateWriteInput($data);

        $created = $errors = [];

        foreach ($data as $orderAddress) {
            try {
                $created[] = $this->writer->upsert(
                    OrderAddressWriteResource::class,
                    $orderAddress,
                    $writeContext,
                    $extender
                );
            } catch (WriteStackException $exception) {
                $errors[] = $exception->toArray();
            }
        }

        $affected = count($created);
        if ($affected === 1) {
            $created = array_shift($created);
        } elseif ($affected > 1) {
            $created = array_merge_recursive(...$created);
        }

        return OrderAddressWriteResource::createWrittenEvent($created, $context, $errors);
    }

    public function create(array $data, TranslationContext $context): OrderAddressWrittenEvent
    {
        $writeContext = $this->createWriteContext($context->getShopUuid());
        $extender = $this->getExtender();

        $this->validateWriteInput($data);

        $created = $errors = [];

        foreach ($data as $orderAddress) {
            try {
                $created[] = $this->writer->insert(
                    OrderAddressWriteResource::class,
                    $orderAddress,
                    $writeContext,
                    $extender
                );
            } catch (WriteStackException $exception) {
                $errors[] = $exception->toArray();
            }
        }

        $affected = count($created);
        if ($affected === 1) {
            $created = array_shift($created);
        } elseif ($affected > 1) {
            $created = array_merge_recursive(...$created);
        }

        return OrderAddressWriteResource::createWrittenEvent($created, $context, $errors);
    }

    private function createWriteContext(string $shopUuid): WriteContext
    {
        $writeContext = new WriteContext();
        $writeContext->set(ShopWriteResource::class, 'uuid', $shopUuid);

        return $writeContext;
    }

    private function getExtender(): FieldExtenderCollection
    {
        $extenderCollection = new FieldExtenderCollection();
        $extenderCollection->addExtender($this->extender);

        $event = new OrderAddressWriteExtenderEvent($extenderCollection);
        $this->eventDispatcher->dispatch(OrderAddressWriteExtenderEvent::NAME, $event);

        return $event->getExtenderCollection();
    }

    private function validateWriteInput(array $data): void
    {
        $malformedRows = [];

        foreach ($data as $index => $row) {
            if (!is_array($row)) {
                $malformedRows[] = $index;
            }
        }

        if (count($malformedRows) === 0) {
            return;
        }

        throw new \InvalidArgumentException('Expected input to be array.');
    }
}
