<?php

/**
 * @author Leon J
 * @since 2017/5/17
 */
class ObjectArrayTest extends TestCase
{
    public function test()
    {
        $obj1 = new stdClass;
        $obj2 = new stdClass;
        $obj3 = new stdClass;
        
        $objArray = new \App\Utils\ObjectArray;
        $objArray->push($obj1);
        $this->assertEquals($obj1, $objArray->get(spl_object_hash($obj1)));
        $this->assertEquals($obj1, $objArray->pop());
        $this->assertTrue($objArray->isEmpty());
        $objArray->push($obj2);
        $objArray->push($obj3);
        $objArray->remove($obj2);
        $this->assertEquals(1, $objArray->length());
    }
}
