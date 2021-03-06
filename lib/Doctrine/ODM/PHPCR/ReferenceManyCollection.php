<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\PHPCR;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Property collection class
 *
 * This class stores all documents or their proxies referenced by a reference many property
 */
class ReferenceManyCollection extends PersistentCollection
{
    private $referencedNodes;
    private $targetDocument;

    /**
     * Creates a new persistent collection.
     *
     * @param DocumentManager $dm              The DocumentManager the collection will be associated with.
     * @param array           $referencedNodes An array of referenced nodes (UUID or path)
     * @param string          $targetDocument  The class name of the target documents
     * @param string          $locale          The locale to use during the loading of this collection
     */
    public function __construct(DocumentManager $dm, array $referencedNodes, $targetDocument, $locale = null)
    {
        $this->dm = $dm;
        $this->referencedNodes = $referencedNodes;
        $this->targetDocument = $targetDocument;
        $this->locale = $locale;
    }

    /**
     * Initializes the collection by loading its contents from the database
     * if the collection is not yet initialized.
     */
    public function initialize()
    {
        if (!$this->initialized) {
            $this->initialized = true;

            $referencedDocs = array();
            $referencedNodes = $this->dm->getPhpcrSession()->getNodesByIdentifier($this->referencedNodes);
            $uow = $this->dm->getUnitOfWork();

            $referencedClass = $this->targetDocument
                ? $this->dm->getMetadataFactory()->getMetadataFor(ltrim($this->targetDocument, '\\'))->name
                : null;

            foreach ($referencedNodes as $referencedNode) {
                $proxy = $referencedClass
                    ? $uow->getOrCreateProxy($referencedNode->getPath(), $referencedClass, $this->locale)
                    : $uow->getOrCreateProxyFromNode($referencedNode, $this->locale);
                $referencedDocs[] = $proxy;
            }

            $this->collection = new ArrayCollection($referencedDocs);
        }
    }

    public function count()
    {
        if (!$this->initialized) {
            return count($this->referencedNodes);
        }

        return parent::count();
    }

    public function isEmpty()
    {
        return !$this->count();
    }
}
