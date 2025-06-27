<?php
/*
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */

namespace MediaWiki\Extension\UserAgreement;

use SpecialPage;
use Html;
use MediaWiki\MediaWikiServices;

class SpecialUserAgreementAudit extends SpecialPage {
    public function __construct() {
        parent::__construct( 'UserAgreementAudit', 'useragreement-audit' );
    }

    /**
     * @inheritDoc
     */
    public function getGroupName() {
        return 'users';
    }

    /**
     * @inheritDoc
     */
    public function doesWrites() {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function isListed() {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function execute( $par ) {
        $this->setHeaders();
        $output = $this->getOutput();
        $this->checkPermissions();

        $output->addWikiMsg( 'useragreement-audit-intro' );

        $this->showAuditTable();
    }

    private function showAuditTable() {
        $output = $this->getOutput();
        $lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
        $dbr = $lb->getConnection( DB_REPLICA );

        $res = $dbr->newSelectQueryBuilder()
            ->select( [
                'ua_user',
                'ua_user_accepted_timestamp',
            ] )
            ->from( 'useragreement' )
            ->orderBy( 'ua_user_accepted_timestamp', 'DESC' )
            ->fetchResultSet();

        // Use $res->valid() to check if there are any rows
        if ( !$res->valid() ) {
            $output->addWikiMsg( 'useragreement-audit-empty' );
            return;
        }

        $table = Html::openElement( 'table', [ 'class' => 'wikitable' ] );
        $table .= Html::openElement( 'tr' );
        $table .= Html::element( 'th', [], $this->msg( 'useragreement-audit-header-user' )->text() );
        $table .= Html::element( 'th', [], $this->msg( 'useragreement-audit-header-email' )->text() );
        $table .= Html::element( 'th', [], $this->msg( 'useragreement-audit-header-timestamp' )->text() );
        $table .= Html::closeElement( 'tr' );

        // Rewind the result set to the beginning
        $res->rewind();

        foreach ( $res as $row ) {
            $user = \User::newFromId( $row->ua_user );
            $username = $user->getName();
            $email = $user->getEmail();
            $timestamp = $row->ua_user_accepted_timestamp;

            $table .= Html::openElement( 'tr' );
            $table .= Html::rawElement( 'td', [], htmlspecialchars( $username ) );
            $table .= Html::rawElement( 'td', [], htmlspecialchars( $email ) );
            $table .= Html::rawElement( 'td', [],  $this->getLanguage()->timeanddate( $timestamp, true ) );
            $table .= Html::closeElement( 'tr' );
        }

        $table .= Html::closeElement( 'table' );
        $output->addHTML( $table );
    }
}
