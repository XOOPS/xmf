<?php
declare(strict_types=1);
namespace Xmf\Security;

trait SerializableTrait
{
    /**
     * Serialize a property for database storage
     */
    protected function serializeProperty($value, string $format = Format::JSON): string
    {
        if ($format === Format::JSON) {
            return Serializer::toJson($value);
        }

        return Serializer::toPhp($value);
    }

    /**
     * Deserialize a property from database
     */
    protected function unserializeProperty(string $data, $default = null, array $allowedClasses = [])
    {
        return Serializer::tryFrom($data, $default, Format::AUTO, $allowedClasses);
    }

    /**
     * Serialize all marked properties
     */
    public function serializeProperties(): array
    {
        $serialized = [];

        foreach ($this->getSerializableProperties() as $property => $format) {
            if (property_exists($this, $property)) {
                $serialized[$property] = $this->serializeProperty($this->$property, $format);
            }
        }

        return $serialized;
    }

    /**
     * Define which properties should be serialized
     * Override in your class
     */
    protected function getSerializableProperties(): array
    {
        return [
            // 'property_name' => Format::JSON
        ];
    }

    /**
     * Migrate serialized data from old to new format
     */
    public function migrateSerializedData(string $property, string $oldData): void
    {
        $format = Serializer::detect($oldData);

        if ($format === Format::PHP || $format === Format::LEGACY) {
            // Deserialize old format
            $value = $this->unserializeProperty($oldData);

            // Re-serialize as JSON
            $newData = $this->serializeProperty($value, Format::JSON);

            // Update property
            $this->setVar($property, $newData);

            // Log migration
            error_log(sprintf(
                '[Migration] %s::%s converted from %s to JSON',
                get_class($this),
                $property,
                $format
            ));
        }
    }
}

// usage example

/*
use Xmf\Security\SerializableTrait;

class ForumForum extends XoopsObject
{
    use SerializableTrait;

    protected function getSerializableProperties(): array
    {
        return [
            'forum_moderators' => Format::JSON,
            'forum_settings' => Format::JSON,
        ];
    }

    public function getModerators(): array
    {
        $data = $this->getVar('forum_moderators');
        return $this->unserializeProperty($data, []);
    }

    public function setModerators(array $moderators): void
    {
        $data = $this->serializeProperty($moderators);
        $this->setVar('forum_moderators', $data);
    }
}
*/
