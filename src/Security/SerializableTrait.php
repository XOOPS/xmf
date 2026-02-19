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
     *
     * Requires the using class to implement setVar() (e.g. XoopsObject).
     * Migration is automatically tracked via Serializer::setLegacyLogger()
     * when the legacy logger is configured.
     *
     * @param string $property Property name to migrate
     * @param string $oldData  Current serialized data
     * @return bool True if migration was performed
     */
    public function migrateSerializedData(string $property, string $oldData): bool
    {
        $format = Serializer::detect($oldData);

        if ($format === Format::PHP || $format === Format::LEGACY) {
            $value = $this->unserializeProperty($oldData);
            $newData = $this->serializeProperty($value, Format::JSON);
            $this->setVar($property, $newData);

            return true;
        }

        return false;
    }
}
