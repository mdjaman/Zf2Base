<?php
/**
* Class QueryRepository
*
* @author Euclécio Josias Rodrigues <eucjosias.64encode@gmail.com>
* @version 3.0
* 
* Necessidade do ḿódulo Doctrine 2 para Zend Framework 2
* Depende do uso das classes com namespaces Doctrine\ORM\EntityRepository e Doctrine\ORM\EntityManager do módulo do Doctrine 2
*
* Classe padrão para uso de pesquisas usando DQL (Doctrine Query Language) 
* Desenvolvida na necessidade de uma classe ao qual fossem criadas funções abstratas e reutilizáveis em determinados tipos de busca
* Pode ser usada para comparadção de buscas com determinado parâmetro, para ordenação de resultados, restrição de valores, limitação de resultados
*
*/
namespace K13Base\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;

Class QueryRepository extends EntityRepository
{
    /**
    * @param EntityManager $em
    */
	protected $em;

    /**
    * @param String $from
    */
    private $from;

    /**
    * @var $queryBuilder
    */
    private $queryBuilder;

	public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->queryBuilder = $this->em->createQueryBuilder();
    }

    /**
    *
    * Define a entidade selecionada e inicia a QUERY
    * Define o valor da varíavel $from
    *
    * @return QueryRepository
    */
    public function from($from)
    {
        $this->from = $from;
        $namespaceFrom = $this->getNamespaceEntity($this->from);
        $this->queryBuilder->select($namespaceFrom)
                           ->from($this->from, $namespaceFrom);

        return $this;
    }

    /**
    *
    * Adicionas as Entidades com LeftJoin
    * @param $entity -> Entidade que será juntada no LeftJoin
    * @param $type -> Tipo da restrição da junção (WITH, ON)
    * @param $onOptions = array('fieldEntity' => array('entityForegein' => 'Namespace\Entity\Foreign', 'fieldForeign' => 'field')) 
    *   Definição das keys:
    *   - fieldEntity -> Atributo da entidade que será restringido comparando com o fieldForeign da entityForegein
    *   - entityForegein -> Entidade que ter[a um atributo de comparação na restrição
    *   - fieldForeign -> Atributo que será comparado com fieldEntity e que irá restringir resultados do Join
    *
    * @example
    * $this->leftJoinAdd('Namespace\Entity\Perfil', 'WITH', array('usuario' => array('entity' => 'Namespace\Entity\Usuario', 'field' => 'id')))
    *   Resultado:
    *       "LEFT JOIN K13Usuario\Entity\Perfil perfil WITH perfil.usuario = usuario.id"
    *
    * @return QueryRepository LEFT JOIN K13Usuario\Entity\Perfil perfil WITH perfil.usuario = usuario.id
    */
    public function leftJoinAdd($entity, $type, array $onOptions)
    {
        $namespaceJoin = $this->getNamespaceEntity($entity);

        $firstOn = true;
        $on = '';
        foreach ($onOptions as $key => $foreign)
        {
            $namespaceOn = $this->getNamespaceEntity($foreign['entity']);
            if($firstOn)
                $firstOn = false;
            else
                $on .= ' AND ';

            $on .= $namespaceJoin.".".$key." = ".$namespaceOn.".".$foreign['field'];
        }

        $this->queryBuilder->leftJoin($entity, $namespaceJoin, $type, $on);

        return $this;
    }

    /**
    *
    * Adiciona uma restrição WHERE do tipo AND (Já verifica se é a primeira comparação evitanto SQL incorretas como "WHERE AND", por exemplo)
    *
    * @return QueryRepository
    */
    public function andWhereAdd($entity, $field, $restriction, $value)
    {
        $namespaceEntity = $this->getNamespaceEntity($entity);
        $this->queryBuilder->andWhere($namespaceEntity.".".$field." ".$restriction." '".$value."'");

        return $this;
    }

    /**
    *
    * Adiciona uma restrição WHERE do tipo OR (Já verifica se é a primeira comparação evitanto SQL incorretas como "WHERE OR", por exemplo)
    *
    * @return QueryRepository
    */
    public function orWhereAdd($entity, $field, $restriction, $value)
    {
        $namespaceEntity = $this->getNamespaceEntity($entity);
        $this->queryBuilder->orWhere($namespaceEntity.".".$field." ".$restriction." '".$value."'");

        return $this;
    }

    /**
    *
    * Define as ordenações dos resultados definidos em $configs['orderBy']
    *
    * @return QueryRepository
    */
    public function orderByAdd($entity, $field, $order)
    {
        $namespaceEntity = $this->getNamespaceEntity($entity);
        $this->queryBuilder->addOrderBy($namespaceEntity.".".$field, $order);

        return $this;
    }

    /**
    *
    * Restringe um limite de resultados
    *
    * @return QueryRepository
    */
    public function limit($limit)
    {
        $this->queryBuilder->setMaxResults($limit);

        return $this;
    }

    /**
    *
    * Retorna a QueryBuilder para analisar com será feita a query
    * @return queryBuilder
    */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
    *
    * Retorna a query da DQL executada
    * @return Object
    */
    public function getQuery()
    {
        return $this->queryBuilder->getQuery();
    }

    /**
    *
    * Retorna o resultado da query
    * @return List
    */
    public function getResult()
    {
        return $this->queryBuilder->getQuery()->getResult();
    }

    /**
    *
    * Pesquisa usando LIKE '%valor%'(case-insensitive) comparando com cada atributo passado em fields da entidade principal e dos joins
    * Os valores que serão comparados serão os passados no array $configs['params'] que quando mais de um, compara atributo por atributo a cada valor do array params
    * @example
    *   @param $params = array('joao', 'email@mail.com')
    *   @param $fields['from'] = 'array('usuario', 'email')
    *   @param $fields['joins'] = array('Namespace\Entity\LeftJoin' => array('nome', 'telefone'))
    *
    *   A restrição ficará:
    *
    *   "WHERE (entity.usuario LIKE '%joao%' OR entity.email LIKE '%joao%' OR leftjoin.nome LIKE '%joao%' OR leftjoin.nome telefone '%joao%') 
    *       AND (entity.usuario LIKE '%mail@mail.com%' OR entity.email LIKE '%mail@mail.com%' OR leftjoin.nome LIKE '%mail@mail.com%' OR leftjoin.telefone LIKE '%mail@mail.com%')"
    *
    * @return QueryRepository
    */
	public function findInFields(array $params, array $fields)
    {
        foreach ($params as $param)
        {
            $where = '';
            $firstField = true;

            $namespaceFrom = $this->getNamespaceEntity($this->from);
            foreach ($fields['from'] as $field)
            {
                if($firstField)
                {
                    $where .= $namespaceFrom.".".$field." LIKE '%".$param."%' ";
                    $firstField = false;
                }
                else
                    $where .= " OR ".$namespaceFrom.".".$field." LIKE '%".$param."%' ";
            }

            if(count($fields['joins']))
            {
                foreach ($fields['joins'] as $entity => $fieldsJoin)
                {
                    $namespaceJoin = $this->getNamespaceEntity($entity);
                    foreach ($fieldsJoin as $field)
                    {
                        if($firstField)
                        {
                            $where .= $namespaceJoin.".".$field." LIKE '%".$param."%' ";
                            $firstField = false;
                        }
                        else
                            $where .= " OR ".$namespaceJoin.".".$field." LIKE '%".$param."%' ";
                    }
                }
            }

            $this->queryBuilder->andWhere($where);
        }

        return $this;
    }

    /**
    *
    * Retorna o apelido de uma entidade de acordo com seu namespace
    * @return String
    */
    protected function getNamespaceEntity($entity)
    {
        $explode_namespace = explode('\\', $entity);
        return strtolower(end($explode_namespace));
    }

    /**
    *
    * Set EntityManager em
    * @return QueryRepository
    */
    public function setEm(EntityManager $em)
	{
		$this->em = $em;

		return $this;
	}

    /**
    *
    * Get em
    * @return em
    */
    public function getEm()
    {
    	return $this->em;
    }
}