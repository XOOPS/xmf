## Debug Mode for Serializer

**Enable during migration testing**
```php
Serializer::enableDebug();
```

**Run your operations**
```php
$data1 = Serializer::from($payload1);
$data2 = Serializer::from($payload2);
```

**Get performance stats**
```php
$stats = Serializer::getDebugStats();
error_log('Serializer stats: ' . json_encode($stats));
```


## XoopsSerializerTrait

```php
class ForumForum extends XoopsObject
{
    use XoopsSerializerTrait;
    
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
```
