/**
 * External dependencies
 */
import styled from '@emotion/styled';

/**
 * iThemes dependencies
 */
import { Text, Heading } from '@ithemes/ui';

/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';

import { SolidLogo } from '@ithemes/security-style-guide';

export const StyledStellarSale = styled.aside`
	position: relative;
	display: flex;
	margin: 1.25rem 1.25rem 0;
	background: #1D202F;
	color: #F9FAF9;
	padding: 1rem;
	justify-content: space-between;
	overflow: hidden;
`;

export const StyledStellarSaleDismiss = styled( Button )`
	color: white;
	z-index: 2;

	&:hover, &:active, &:focus {
		color: white !important;
	}
`;

export const StyledStellarSaleContent = styled.div`
	z-index: 1;
	max-width: 50rem;
	display: grid;
	grid-template-columns: ${ ( { isSmall } ) => isSmall ? '1fr 1fr' : 'auto' };
	gap: 1rem 1.5rem;
	align-items: end;
	justify-items: start;
	padding: ${ ( { isSmall } ) => isSmall
		? '1.25rem 4.45rem 0.65rem 2.9rem'
		: '1.25rem 4.45rem 0.65rem 0.25rem'
};
`;

export const StyledStellarSaleHeading = styled( Heading )`
	grid-column: ${ ( { isSmall } ) => ! isSmall && 'span 2' };
  
	strong {
		font-size: 1.5rem;
	}
`;

export const StyledStellarSaleButton = styled.a`
	display: inline-flex;
	min-width: max-content;
	padding: 0.75rem 1.75rem;
	justify-content: center;
	align-items: center;
	color: #ffffff;
	font-size: 0.83569rem;
	text-align: center;
	text-transform: uppercase;
	text-decoration: none;
	border-radius: 7.8125rem;
	background: #6817C5;

	&:hover, &:active, &:focus {
		color: inherit;
		opacity: 0.75;
	}
`;

export const StyledStellarSaleLink = styled( Text )`
	text-decoration: underline;
	align-self: ${ ( { isSmall } ) => isSmall ? 'start' : 'center' };

	&:hover, &:active, &:focus {
		color: inherit;
		font-style: oblique;
	}
`;

export const StyledStellarSaleGraphic = styled( Graphic )`
	position: absolute;
	right: ${ ( { isWide } ) => isWide ? '5rem' : '-2rem' };
	top: 50%;
	transform: translateY(-50%);
	opacity: ${ ( { isWide } ) => isWide ? 1 : 0.40 };
`;

function Graphic( { className } ) {
	return <SolidLogo className={ className } />;
}
